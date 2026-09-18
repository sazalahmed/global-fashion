# Asset Module

## Overview
Manages fixed assets for the business including acquisition, categorization, depreciation tracking, and maintenance records. Supports multi-branch asset allocation.

## Controllers
- **AssetController** - CRUD for assets, including asset categories and maintenance record management

## Models
- **Asset** - Fixed asset record (name, category, purchase date, cost, current value, depreciation method, branch, status)
- **AssetCategory** - Asset classification groups (e.g., Furniture, Electronics, Vehicles)
- **AssetMaintenance** - Maintenance/repair records linked to an asset (date, cost, description, next scheduled)

## Services
- **AssetService** - Business logic for asset lifecycle management, depreciation calculations, and maintenance scheduling

## Routes
- **Asset CRUD** - Create, read, update, delete assets
- **Asset Categories** - Manage asset classification groups
- **Maintenance Records** - Log and view maintenance history per asset

## Settings / Configuration
- Default depreciation method (straight-line, reducing balance)
- Depreciation rate defaults per category

## Dependencies
- **Accounting** - Depreciation entries are posted as journal entries to the accounting module
- **Branch** - Assets are assigned to specific branches for multi-location tracking
