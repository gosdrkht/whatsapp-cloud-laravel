# Meta WhatsApp Business Cloud API Setup Guide

Before sending or receiving WhatsApp messages with `lankadev/whatsapp-cloud-laravel`, you need credentials from the official **Meta for Developers** platform. This guide walks you through both **quick sandbox testing** and **production permanent tokens**.

---

## Step 1: Create a Meta Developer App

1. Go to [developers.facebook.com](https://developers.facebook.com/) and sign in with your Facebook account.
2. Click **My Apps** → **Create App**.
3. Choose **Other** → **Business** (or select the **Connect with customers through WhatsApp** use case).
4. Enter an **App Name** (e.g. `My Store WhatsApp`), contact email, and optionally link a **Meta Business Portfolio** (required for production).
5. Click **Create App**.

---

## Step 2: Add the WhatsApp Product & Get Sandbox Credentials

1. On your App Dashboard, find **WhatsApp** and click **Set up**.
2. Open **WhatsApp** → **API Setup** in the left sidebar.
3. Copy the following values into your Laravel `.env` file:
   - **Temporary access token** → `WHATSAPP_ACCESS_TOKEN` *(valid for 24 hours; see Step 5 for a permanent production token)*
   - **Phone number ID** → `WHATSAPP_PHONE_NUMBER_ID`
   - **WhatsApp Business Account ID** → `WHATSAPP_BUSINESS_ACCOUNT_ID`
4. Under **To**, click **Manage phone number list** and add your own WhatsApp number (you will verify it with a 6-digit OTP). Meta's free test number can only send messages to verified test recipients until you add your own phone number.

---

## Step 3: Copy Your App Secret (Required for Webhook Security)

1. In the left sidebar, go to **App Settings** → **Basic**.
2. Click **Show** next to **App Secret** and copy it into `.env`:

```env
WHATSAPP_APP_SECRET=your_meta_app_secret
```

This package automatically verifies Meta's `X-Hub-Signature-256` header against the raw request body using `WHATSAPP_APP_SECRET`.

---

## Step 4: Configure Webhooks (Incoming Messages & Delivery Receipts)

1. Choose any strong random string for your verify token and add it to `.env`:

```env
WHATSAPP_VERIFY_TOKEN=my_random_secure_verify_token
```

2. Run the Artisan health check to confirm your callback URL:

```bash
php artisan whatsapp:webhook
```

3. In the Meta App Dashboard, go to **WhatsApp** → **Configuration** → **Webhook** → **Edit**:
   - **Callback URL**: `https://your-domain.com/whatsapp/webhook` *(must be HTTPS; for local testing use ngrok, Expose, or Cloudflare Tunnel)*
   - **Verify token**: the exact same string you set in `WHATSAPP_VERIFY_TOKEN`
4. Click **Verify and save**.
5. Under **Webhook fields**, click **Manage** and subscribe to **`messages`**.

---

## Step 5: Generate a Permanent Access Token (For Production)

The token on the **API Setup** page expires after **24 hours**. For production apps, create a **System User** token that never expires:

1. Open [Meta Business Settings](https://business.facebook.com/settings/).
2. Go to **Users** → **System Users** → click **Add**.
3. Give it a name (e.g. `Laravel WhatsApp Bot`) and set the role to **Admin**.
4. Click **Assign Assets** → **Apps** → select your WhatsApp app → enable **Full Control** → **Save Changes**.
5. Click **Generate New Token**:
   - Select your app
   - Set **Token Expiration** to **Never**
   - Grant permissions:
     - `whatsapp_business_messaging`
     - `whatsapp_business_management`
6. Copy the generated token and replace `WHATSAPP_ACCESS_TOKEN` in your production `.env`.

> **Security Reminder**: Never commit `.env` or hardcode your access token in source code.

---

## Step 6: Verify Your Setup from Laravel

Send a test message directly from your terminal:

```bash
php artisan whatsapp:test 94771234567 --message="Hello from WhatsApp Cloud Laravel!"
```

List your approved message templates:

```bash
php artisan whatsapp:templates --status=APPROVED
```

---

## Production Checklist

- [ ] Replace the 24-hour temporary token with a **Permanent System User Token**.
- [ ] Add your own live business phone number under **WhatsApp** → **API Setup** → **Add phone number**.
- [ ] Add a payment method in **Meta Business Settings** → **Billing & Payments** (required for paid template conversations).
- [ ] Keep `WHATSAPP_VERIFY_SIGNATURE=true` and `WHATSAPP_WEBHOOK_IDEMPOTENCY=true` enabled in production.
- [ ] Set `WHATSAPP_DEFAULT_COUNTRY_CODE=94` (or your country's calling code) if your app stores local phone numbers like `0771234567`.
- [ ] Respect Meta's opt-in rules, 24-hour customer service window, and template approval guidelines.
