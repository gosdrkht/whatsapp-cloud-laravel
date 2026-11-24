<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LankaDev\WhatsApp\Client\HttpClientInterface;
use LankaDev\WhatsApp\Client\LaravelHttpClient;
use LankaDev\WhatsApp\Client\WhatsAppClient;
use LankaDev\WhatsApp\Client\WhatsAppConfig;
use LankaDev\WhatsApp\Console\WhatsAppTemplatesCommand;
use LankaDev\WhatsApp\Console\WhatsAppTestCommand;
use LankaDev\WhatsApp\Console\WhatsAppWebhookCommand;
use LankaDev\WhatsApp\Notifications\WhatsAppChannel;
use LankaDev\WhatsApp\Support\ConversationWindow;
use LankaDev\WhatsApp\Webhooks\VerifyWebhookSignature;
use LankaDev\WhatsApp\Webhooks\WebhookController;
use LankaDev\WhatsApp\Webhooks\WebhookIdempotencyStore;
use LankaDev\WhatsApp\Webhooks\WebhookProcessor;
use Psr\Log\NullLogger;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(WhatsAppConfig::class, function (): WhatsAppConfig {
            return WhatsAppConfig::fromArray((array) config('whatsapp', []));
        });

        $this->app->singleton(HttpClientInterface::class, function ($app): HttpClientInterface {
            return new LaravelHttpClient($app->make(HttpFactory::class));
        });

        $this->app->singleton(WhatsAppClient::class, function ($app): WhatsAppClient {
            $logger = new NullLogger;

            if (config('whatsapp.logging.enabled', false)) {
                $channel = config('whatsapp.logging.channel');
                $logger = is_string($channel) && $channel !== ''
                    ? Log::channel($channel)
                    : Log::getFacadeRoot();
            }

            return new WhatsAppClient(
                config: $app->make(WhatsAppConfig::class),
                http: $app->make(HttpClientInterface::class),
                logger: $logger,
            );
        });

        $this->app->singleton(ConversationWindow::class, function ($app): ConversationWindow {
            return new ConversationWindow($app->make(CacheFactory::class));
        });

        $this->app->singleton(WhatsAppManager::class, function ($app): WhatsAppManager {
            return new WhatsAppManager(
                $app->make(WhatsAppClient::class),
                $app->make(ConversationWindow::class),
            );
        });

        $this->app->alias(WhatsAppManager::class, 'whatsapp');

        $this->app->singleton(WebhookIdempotencyStore::class, function ($app): WebhookIdempotencyStore {
            return new WebhookIdempotencyStore($app->make(CacheFactory::class));
        });

        $this->app->singleton(WebhookProcessor::class, function ($app): WebhookProcessor {
            return new WebhookProcessor(
                $app->make(Dispatcher::class),
                $app->make(WebhookIdempotencyStore::class),
                $app->make(ConversationWindow::class),
            );
        });

        $this->app->bind(WhatsAppChannel::class, function ($app): WhatsAppChannel {
            return new WhatsAppChannel($app->make(WhatsAppManager::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/whatsapp.php' => config_path('whatsapp.php'),
            ], 'whatsapp-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/create_whatsapp_messages_table.php.stub' => database_path('migrations/' . date('Y_m_d_His') . '_create_whatsapp_messages_table.php'),
            ], 'whatsapp-migrations');

            $this->commands([
                WhatsAppTestCommand::class,
                WhatsAppTemplatesCommand::class,
                WhatsAppWebhookCommand::class,
            ]);
        }

        $this->registerNotificationChannel();
        $this->registerWebhookRoutes();
    }

    private function registerNotificationChannel(): void
    {
        $this->app->make(ChannelManager::class)->extend('whatsapp', function ($app): WhatsAppChannel {
            return $app->make(WhatsAppChannel::class);
        });
    }

    private function registerWebhookRoutes(): void
    {
        if (! config('whatsapp.webhook.enabled', true)) {
            return;
        }

        $path = (string) config('whatsapp.webhook.path', 'whatsapp/webhook');
        $middleware = (array) config('whatsapp.webhook.middleware', ['api']);

        Route::middleware($middleware)->group(function () use ($path): void {
            Route::get($path, [WebhookController::class, 'verify'])
                ->name('whatsapp.webhook.verify');

            Route::post($path, [WebhookController::class, 'handle'])
                ->middleware(VerifyWebhookSignature::class)
                ->name('whatsapp.webhook.handle');
        });
    }
}
