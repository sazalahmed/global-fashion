# Marketing Module

## Overview
Manages marketing campaigns across email and SMS channels, and handles customer loyalty program transactions and point tracking.

## Controllers
- **MarketingController** -- Manages email campaigns, SMS campaigns, and loyalty program operations.

## Models
- **EmailCampaign** -- Email marketing campaigns with subject, content, recipient list, schedule, and delivery status.
- **SmsCampaign** -- SMS marketing campaigns with message content, recipient list, schedule, and delivery status.
- **LoyaltyTransaction** -- Records loyalty point earn/redeem transactions linked to customers.

## Services
- **MarketingService** -- Business logic for campaign creation, scheduling, sending, audience segmentation, and loyalty point management (earning rules, redemption, balance queries).

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/marketing/email-campaigns` | Email campaign CRUD |
| POST | `/marketing/email-campaigns/{id}/send` | Send email campaign |
| Resource | `/marketing/sms-campaigns` | SMS campaign CRUD |
| POST | `/marketing/sms-campaigns/{id}/send` | Send SMS campaign |
| GET | `/marketing/loyalty` | Loyalty program dashboard |
| POST | `/marketing/loyalty/earn` | Record loyalty points earned |
| POST | `/marketing/loyalty/redeem` | Redeem loyalty points |

## Settings / Configuration
- SMS gateway configuration (BulkSMSBD) -- API key and sender ID.
- Email provider configuration -- SMTP or API-based provider settings.
- Loyalty program rules -- points per BDT spent, redemption rate, expiry policy.

## Dependencies
- **Customer** -- Campaigns target customer segments; loyalty points are tracked per customer.
- **Setting** -- Global SMS gateway and email provider settings.
