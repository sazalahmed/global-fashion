# Setting Module

## Overview
Central configuration module for the entire system. Manages business profile, tax/VAT settings, invoice formats, email configuration and templates, printer setup, sidebar layout, system logs, maintenance mode, cache management, and webhook integrations.

## Controllers
- **SettingController** — Handles general system settings (business profile, tax, invoice format, localization, SMS gateway, sidebar configuration, maintenance mode, and cache management).
- **EmailSettingController** — Manages email server configuration (SMTP/mailgun) and email template CRUD for transactional emails (invoice, welcome, password reset, etc.).
- **PrinterController** — CRUD for printer devices used in POS and invoice printing, including thermal and A4 printer profiles.
- **SystemLogController** — Viewing, downloading, and clearing Laravel application logs and system activity logs.

## Models
- **Setting** — Key-value store for all system-wide configuration. Supports grouped settings (business, tax, invoice, localization, sms, etc.).
- **EmailTemplate** — Stores email templates with subject, body (Blade-compatible), and variable placeholders for transactional emails.
- **Printer** — Printer device profiles with name, type (thermal/A4), connection type (USB/network/Bluetooth), IP address, and default paper size.
- **Webhook** — Webhook endpoint registrations for external integrations, storing URL, events subscribed, secret key, and active status.

## Services
- **SettingService** — Business logic for reading, updating, and caching system settings. Provides helper methods for retrieving settings by group or key.
- **LogViewerService** — Parses and presents Laravel log files, supports filtering by level (error, warning, info), date range, and keyword search.

## Form Requests
- **UpdateSettingsRequest** - Validates system settings updates (business, tax, invoice, SMS, etc.)
- **SaveSidebarConfigRequest** - Validates sidebar configuration (nested array with label, visible, order)
- **StoreWebhookRequest** - Validates webhook creation (name, URL, secret, events)
- **SaveEmailConfigRequest** - Validates email/SMTP configuration
- **TestEmailRequest** - Validates test email address
- **UpdateEmailTemplateRequest** - Validates email template updates (name, subject, body)
- **StorePrinterRequest** - Validates printer creation (name, IP, port, type, connection, paper width, purpose)
- **UpdatePrinterRequest** - Validates printer update (extends StorePrinterRequest)

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/settings` | Main settings page (tabbed) |
| PUT | `/settings` | Update general settings |
| GET | `/settings/email` | Email configuration |
| PUT | `/settings/email` | Update email settings |
| GET | `/settings/email-templates` | List email templates |
| POST | `/settings/email-templates` | Create email template |
| PUT | `/settings/email-templates/{id}` | Update email template |
| DELETE | `/settings/email-templates/{id}` | Delete email template |
| GET | `/settings/sidebar` | Sidebar configuration |
| PUT | `/settings/sidebar` | Update sidebar layout |
| POST | `/settings/maintenance` | Toggle maintenance mode |
| POST | `/settings/cache/clear` | Clear application cache |
| GET | `/settings/logs` | View system logs |
| GET | `/settings/logs/download` | Download log file |
| POST | `/settings/logs/clear` | Clear log files |
| GET | `/settings/printers` | List printers |
| POST | `/settings/printers` | Add printer |
| PUT | `/settings/printers/{id}` | Update printer |
| DELETE | `/settings/printers/{id}` | Remove printer |
| GET | `/settings/webhooks` | List webhooks |
| POST | `/settings/webhooks` | Create webhook |
| PUT | `/settings/webhooks/{id}` | Update webhook |
| DELETE | `/settings/webhooks/{id}` | Remove webhook |

## Settings / Configuration
- **Business Profile** — Company name, address, phone, email, logo, BIN number.
- **Tax / VAT** — Default tax rate (15% for Bangladesh), tax registration number, Mushak form settings.
- **Invoice & Receipt** — Invoice number format, prefix, default terms, footer text, template selection.
- **Localization** — Currency (BDT), date format (DD MMM YYYY), timezone (Asia/Dhaka), language.
- **SMS Gateway** — Provider (BulkSMSBD), API credentials, sender ID.
- **Sidebar** — Menu item visibility and ordering per role.
- **Printers** — Default printer for POS receipts and A4 invoices.

## Dependencies
- **Core** — Provides foundational services and the master layout that settings integrate with.
