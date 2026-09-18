# Unit Module

## Overview
Manages units of measurement used across products and inventory. Supports standard units (pcs, kg, ltr, box, dozen, etc.) and allows defining custom units for specialized business needs.

## Controllers
- **UnitController** — Full CRUD for units of measurement, including listing, creating, editing, and deleting units.

## Models
- **Unit** — Unit record with name, short name/abbreviation, and active status. Examples: Piece (pcs), Kilogram (kg), Litre (ltr), Box (box), Dozen (dzn), Meter (m), Carton (ctn).

## Services
- **UnitService** — Business logic for unit management, validation of unit usage before deletion, and providing unit lists for product forms.

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/units` | List all units |
| GET | `/units/create` | Create unit form |
| POST | `/units` | Store new unit |
| GET | `/units/{id}/edit` | Edit unit form |
| PUT | `/units/{id}` | Update unit |
| DELETE | `/units/{id}` | Delete unit |

## Settings / Configuration
- Default unit for new products.

## Dependencies
- **Product** — Products reference units for their base unit of measurement.
