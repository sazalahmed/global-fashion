# Category Module

## Overview
Hierarchical product category management. Supports parent-child category trees for organizing products into nested groups.

## Controllers
- **CategoryController** - CRUD operations for categories with hierarchy support

## Models
- **Category** - Category record (name, parent_id, description, image, is_active) with self-referencing parent-child relationships

## Services
- **CategoryService** - Business logic for category tree management, reordering, and hierarchy validation

## Routes
- **Category CRUD** - Create, read, update, delete categories with hierarchical (parent/child) structure

## Settings / Configuration
- No module-specific settings.

## Dependencies
- **Product** - Categories are assigned to products for classification, filtering, and navigation
