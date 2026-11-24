<?php

declare(strict_types=1);

namespace LankaDev\WhatsApp\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class WhatsAppMakeNotificationCommand extends Command
{
    protected $signature = 'whatsapp:make-notification
                            {name : The name of the notification class (e.g. OrderConfirmationNotification)}
                            {--template= : Pre-fill a Meta template name instead of a text message}
                            {--force : Overwrite the notification class if it already exists}';

    protected $description = 'Scaffold a new Laravel Notification class pre-wired for the WhatsApp channel';

    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $rawName = trim((string) $this->argument('name'));
        $className = str_replace(['/', '\\'], '\\', $rawName);
        $segments = explode('\\', $className);

        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment) !== 1) {
                $this->error("Invalid notification class name [{$rawName}].");

                return self::FAILURE;
            }
        }

        $shortName = array_pop($segments);
        $subNamespace = $segments !== [] ? '\\' . implode('\\', $segments) : '';
        $relativePath = implode(DIRECTORY_SEPARATOR, array_merge($segments, ["{$shortName}.php"]));

        $targetPath = app_path('Notifications' . DIRECTORY_SEPARATOR . $relativePath);

        if ($this->files->exists($targetPath) && ! $this->option('force')) {
            $this->error("Notification already exists at [{$targetPath}]. Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($targetPath));

        $template = $this->option('template');
        $body = is_string($template) && $template !== ''
            ? $this->templateBody($template)
            : $this->textBody();

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Notifications{$subNamespace};

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use LankaDev\WhatsApp\Notifications\WhatsAppMessage;

class {$shortName} extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object \$notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp(object \$notifiable): WhatsAppMessage
    {
{$body}
    }
}

PHP;

        $this->files->put($targetPath, $stub);

        $this->info("WhatsApp notification created successfully: {$targetPath}");

        return self::SUCCESS;
    }

    private function templateBody(string $templateName): string
    {
        $safe = addslashes($templateName);

        return <<<PHP
        return WhatsAppMessage::template('{$safe}', 'en', [
            // 'customer' => \$notifiable->name,
        ]);
PHP;
    }

    private function textBody(): string
    {
        return <<<'PHP'
        return WhatsAppMessage::text('Hello! This is a WhatsApp notification.');
PHP;
    }
}
