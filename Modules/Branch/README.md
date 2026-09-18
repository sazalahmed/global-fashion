# Branch Module

## Overview
Multi-branch management for BizPOS Pro. Defines business locations/branches and enables branch-level data filtering across the entire application.

## Controllers
- **BranchController** - CRUD operations for branches

## Models
- **Branch** - Branch record (name, address, phone, email, is_active, is_default)

## Services
- **BranchService** - Business logic for branch management and default branch resolution

## Routes
- **Branch CRUD** - Create, read, update, delete branches

## Settings / Configuration
- Default branch selection
- Branch-specific settings (address, contact info)

## Dependencies
- **Core** - Base module dependency. The Branch module is used by many other modules (Sales, Purchases, Inventory, Assets, Employees) for multi-branch data scoping and filtering.
