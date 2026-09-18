# Variant Module

## Overview
Manages product variants through a flexible attribute system. Supports defining variant attributes (e.g., Size, Color, Material), their possible values (e.g., S/M/L/XL, Red/Blue/Green), and generating product variant combinations with individual SKU, price, and stock tracking.

## Controllers
- **VariantController** — CRUD for variant attributes and their values, plus product variant generation and management.

## Models
- **ProductVariant** — A specific variant combination for a product (e.g., "T-Shirt - Red - Large") with its own SKU, price adjustment, cost, stock quantity, and barcode.
- **VariantAttribute** — Defines an attribute type (e.g., Size, Color, Weight, Material).
- **VariantAttributeValue** — Possible values for an attribute (e.g., Size: S, M, L, XL, XXL).

## Services
- **VariantService** — Business logic for attribute management, value assignment, variant combination generation (cartesian product of selected attributes), and variant price/stock management.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/variants/attributes` | List all variant attributes |
| POST | `/variants/attributes` | Create new attribute |
| PUT | `/variants/attributes/{id}` | Update attribute |
| DELETE | `/variants/attributes/{id}` | Delete attribute |
| GET | `/variants/attributes/{id}/values` | List attribute values |
| POST | `/variants/attributes/{id}/values` | Add attribute value |
| PUT | `/variants/values/{id}` | Update attribute value |
| DELETE | `/variants/values/{id}` | Delete attribute value |
| POST | `/products/{id}/variants/generate` | Generate variant combinations |
| PUT | `/products/{id}/variants/{variantId}` | Update variant details |
| DELETE | `/products/{id}/variants/{variantId}` | Delete product variant |

## Settings / Configuration
- Maximum number of attributes per product.
- Whether to auto-generate SKUs for variant combinations.
- SKU format pattern for generated variants.

## Dependencies
- **Product** — Variants are created as extensions of product records.
