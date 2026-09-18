# Brand Module

## Overview
Manages product brands/manufacturers. Provides brand CRUD operations used by the Product module for product classification.

## Controllers
- **BrandController** - CRUD operations for brands

## Models
- **Brand** - Brand record (name, description, logo, is_active)

## Services
- **BrandService** - Business logic for brand management

## Routes
- **Brand CRUD** - Create, read, update, delete brands

## Settings / Configuration
- No module-specific settings.

## Dependencies
- **Product** - Brands are assigned to products for classification and filtering
