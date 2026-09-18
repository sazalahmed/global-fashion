# Dashboard Module

## Overview
Main landing page after login. Displays key business metrics, sales statistics, revenue charts, top-selling products, recent transactions, low stock alerts, and payment method breakdowns.

## Controllers
- **DashboardController** - Aggregates data from multiple modules and renders the dashboard view

## Services
- **DashboardService** - Compiles dashboard statistics: sales totals, revenue trends, top products, low stock items, payment breakdowns, and recent activity

## Form Requests
- **DashboardFilterRequest** - Validates optional filter parameters (date range, branch, period)

## Routes
- **Dashboard Index** - Main dashboard page (default route after login)

## Features
- **Sales Stats** - Today's sales, weekly/monthly totals, comparison with previous periods
- **Revenue Charts** - Line/bar charts showing revenue trends over time (powered by Chart.js)
- **Top Products** - Best-selling products by quantity and revenue
- **Recent Sales** - Latest sale transactions with status badges
- **Low Stock Alerts** - Products below minimum stock threshold
- **Payment Breakdown** - Sales distribution by payment method (Cash, bKash, Nagad, Card, etc.)

## Settings / Configuration
- Dashboard date range defaults
- Number of items displayed per widget

## Dependencies
- **Sale** - Sales totals, recent sales, revenue data
- **Product** - Top products, low stock alerts
- **Customer** - Customer-related metrics
- **Payment** - Payment method breakdown
- **Inventory** - Stock level data for low stock alerts
