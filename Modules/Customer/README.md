# Customer Module

## Overview
Complete customer management for BizPOS Pro. Handles customer records, customer groups, geographic areas (hierarchical), ledger history, due collection, advance payments, and offset dues.

## Controllers
- **CustomerController** - Full CRUD for customers plus ledger view, due receive, advance management, quick store (AJAX), offset due, and search
- **CustomerGroupController** - CRUD for customer groups/tiers (e.g., Regular, VIP, Wholesale)
- **AreaController** - CRUD for geographic areas with hierarchical parent-child structure

## Models
- **Customer** - Customer record (name, phone, email, address, group, area, balance, advance, status)
- **CustomerGroup** - Customer classification groups with optional pricing rules
- **Area** - Geographic areas with hierarchy support (Division > District > Thana)

## Services
- **CustomerService** - Business logic for customer management, due calculations, advance handling, offset dues, and ledger generation

## Routes
- **Customer CRUD** - Create, read, update, delete customers
- **Customer Groups CRUD** - Manage customer classification groups
- **Areas CRUD** - Manage geographic areas with parent-child hierarchy
- **Customer Ledger** - View full transaction history for a customer
- **Due Receive** - Collect outstanding dues from customers
- **Advance Management** - Record and apply customer advance payments
- **Quick Store** - AJAX endpoint for creating customers inline (e.g., from POS or sale form)
- **Offset Due** - Adjust/offset customer dues
- **Customer Search** - AJAX search endpoint for customer lookup

## Settings / Configuration
- Default customer group for new customers
- Credit limit defaults

## Dependencies
- **Sale** - Customer sales history and outstanding invoices
- **Payment** - Payment records linked to customers
- **Branch** - Customers can be scoped to branches
