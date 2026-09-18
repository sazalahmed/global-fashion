# POS Module

## Overview
Point of Sale terminal interface for in-store sales. Provides real-time product search, barcode scanning, multi-payment support, receipt printing, customer management, daily settlement, and offline PWA support via service worker.

## Controllers
- **POSController** -- POS interface rendering, sale processing, product/customer search, barcode lookup, quick customer creation, receipt generation, and POS settings.
- **SettlementController** -- Daily POS settlement (cash count, payment method reconciliation, shift closing).

## Services
- **POSService** -- Business logic for POS sale processing, cart management, discount application, multi-payment splitting, barcode resolution, customer advance handling, and receipt data preparation.
- **SettlementService** -- Settlement calculations, cash drawer reconciliation, and shift summary generation.

## Form Requests
- **ProcessSaleRequest** - Validates POS sale processing (cart items, payments, customer, discounts, tax)
- **QuickAddCustomerRequest** - Validates quick customer creation (name, phone)
- **SavePosSettingsRequest** - Validates POS configuration settings
- **SettlementFilterRequest** - Validates settlement filter parameters (date, branch, cashier)

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/pos` | POS terminal interface |
| POST | `/pos/sale` | Process a POS sale |
| GET | `/pos/search-products` | AJAX product search |
| GET | `/pos/search-customers` | AJAX customer search |
| GET | `/pos/barcode/{code}` | Barcode lookup |
| POST | `/pos/customer-advance` | Apply customer advance to sale |
| POST | `/pos/quick-customer` | Quick-add a new customer |
| GET | `/pos/receipt/{id}` | View/print receipt |
| GET | `/pos/settings` | POS settings page |
| POST | `/pos/settings` | Update POS settings |
| GET | `/pos/settlement` | Settlement interface |
| POST | `/pos/settlement` | Process daily settlement |

## Features
- **Real-time search** -- AJAX-powered product and customer search with debouncing.
- **Barcode scanning** -- Hardware barcode scanner and camera-based scanning support.
- **Multi-payment** -- Split payments across Cash, bKash, Nagad, Rocket, Card, and Bank Transfer.
- **Receipt printing** -- Thermal receipt printing with configurable templates.
- **Offline PWA support** -- Service worker (`sw-pos.js`) enables offline POS operation with IndexedDB queue; transactions sync when connectivity is restored.

## Settings / Configuration
POS-specific settings managed through the POS settings page:
- Default payment method
- Receipt template and printer configuration
- Sound effects toggle
- Quick-access product categories
- Barcode scanner input mode

## Dependencies
- **Product** -- Products displayed and sold through the POS.
- **Customer** -- Customer lookup, quick creation, and advance payments.
- **Sale** -- POS transactions create sale records.
- **Payment** -- Payments are processed and recorded per sale.
- **Inventory** -- Stock levels are decremented on sale completion.
