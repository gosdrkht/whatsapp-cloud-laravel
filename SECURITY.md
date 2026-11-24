# Security Policy

## Supported Versions

| Version | Supported |
| ------- | --------- |
| 1.x     | Yes       |

## Security Best Practices

- Never log or expose `WHATSAPP_ACCESS_TOKEN` or `WHATSAPP_APP_SECRET`.
- Always keep `WHATSAPP_VERIFY_SIGNATURE=true` in production so incoming Meta webhooks are verified against the raw request body using `X-Hub-Signature-256`.
- Keep `WHATSAPP_WEBHOOK_IDEMPOTENCY=true` enabled to prevent duplicate webhook replay processing.

## Reporting a Vulnerability

If you discover a security vulnerability in this package, please **do not** open a public GitHub issue. Instead, report it privately via GitHub Security Advisories on the repository (`Security` -> `Report a vulnerability`). We will respond promptly and coordinate a fix.
