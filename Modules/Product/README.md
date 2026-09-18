# Product Module

## Overview
Core product management including product creation, SKU/barcode generation, variant management, image uploads, bulk updates, and product duplication.

## Controllers
- **ProductController** -- Full product CRUD, SKU/barcode generation, variant attribute and variant management, bulk update operations, and product duplication.

## Models
- **Product** -- Core product record with name, SKU, barcode, description, cost price, selling price, tax, stock alert level, status, and relationships to category, brand, and unit.
- **ProductImage** -- Product images with file path, sort order, and primary flag.
- **Tag** -- Tags for product classification and search (many-to-many relationship with products).

## Services
- **ProductService** -- Business logic for product creation with variants, SKU/barcode auto-generation, image management, bulk update processing, duplication with related data, and product search/filtering.

## Routes
| Method | Route | Description |
|--------|-------|-------------|
| Resource | `/products` | Product CRUD |
| POST | `/products/generate-sku` | Auto-generate SKU |
| POST | `/products/generate-barcode` | Auto-generate barcode |
| GET | `/products/{id}/variants` | Manage product variants |
| POST | `/products/{id}/variants` | Store variant combinations |
| PUT | `/products/{id}/variants` | Update variant details |
| POST | `/products/bulk-update` | Bulk update products (price, status, category) |
| POST | `/products/{id}/duplicate` | Duplicate product with options |

## Settings / Configuration
No module-specific settings. Product-related defaults (tax rate, SKU format) are configured in the Setting module.

## Dependencies
- **Category** -- Products belong to categories.
- **Brand** -- Products optionally belong to a brand.
- **Unit** -- Products have a unit of measurement.
- **Variant** -- Variant attributes (size, color) and combinations.
- **Barcode** -- Barcode generation and label printing.
- **Inventory** -- Stock levels are tracked per product/variant per location.
