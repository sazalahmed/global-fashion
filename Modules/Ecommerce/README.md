# Ecommerce Module

## Overview
Full ecommerce storefront management including online orders, coupons, shipping zones, courier providers, banners, homepage sections, product collections, flash deals, blog posts, and fraud detection.

## Controllers
- **EcommerceController** -- Manages orders, coupons, shipping zones, courier providers, and ecommerce settings.
- **ContentController** -- Manages storefront content: banners, homepage sections, product collections, flash deals, and blog posts.

## Models
- **EcommerceOrder** -- Online orders placed through the storefront with status tracking.
- **EcommerceOrderItem** -- Individual line items within an ecommerce order.
- **Coupon** -- Discount coupons with code, type (percentage/fixed), validity period, and usage limits.
- **EcommerceSetting** -- Key-value store for ecommerce configuration (store name, currency, policies).
- **ShippingZone** -- Geographic shipping zones with associated rates and delivery estimates.
- **CourierProvider** -- Courier/logistics provider configuration (Pathao, Steadfast, eCourier, Redx, etc.).
- **Banner** -- Promotional banners displayed on the storefront.
- **HomepageSection** -- Configurable sections for the storefront homepage layout.
- **ProductCollection** -- Curated groups of products for display on the storefront.
- **FlashDeal** -- Time-limited deals with countdown, discount, and product selection.
- **BlogPost** -- Blog/content articles for SEO and customer engagement.

## Services
- **EcommerceService** -- Order processing, coupon validation, shipping rate calculation, and courier integration.
- **FraudCheckService** -- Evaluates orders for fraud risk based on patterns, address mismatches, and order history.
- **ContentManagementService** -- CRUD logic for banners, homepage sections, collections, flash deals, and blog posts.
- **StorefrontService** -- Assembles storefront data (featured products, active deals, banners) for rendering.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| GET/POST | `/ecommerce/orders` | List / manage ecommerce orders |
| PATCH | `/ecommerce/orders/{id}/status` | Update order status |
| Resource | `/ecommerce/coupons` | Coupon CRUD |
| Resource | `/ecommerce/shipping-zones` | Shipping zone CRUD |
| Resource | `/ecommerce/courier-providers` | Courier provider CRUD |
| Resource | `/ecommerce/banners` | Banner CRUD |
| Resource | `/ecommerce/homepage-sections` | Homepage section CRUD |
| Resource | `/ecommerce/collections` | Product collection CRUD |
| Resource | `/ecommerce/flash-deals` | Flash deal CRUD |
| Resource | `/ecommerce/blog-posts` | Blog post CRUD |
| POST | `/ecommerce/orders/{id}/fraud-check` | Run fraud check on order |

## Settings / Configuration
- Store name and branding
- Default currency and display format
- Shipping rates per zone
- Courier API keys and credentials (Pathao, Steadfast, eCourier, Redx, Paperfly)
- Minimum order amount, free shipping threshold
- Payment gateway configuration for online checkout

## Dependencies
- **Product** -- Products listed on the storefront.
- **Customer** -- Customer accounts, addresses, and order history.
- **Delivery** -- Fulfillment of ecommerce orders via delivery challans.
- **Payment** -- Payment processing for online orders.
