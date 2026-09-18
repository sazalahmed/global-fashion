<?php

/*
|--------------------------------------------------------------------------
| Navigation (Single Source of Truth for page/navigation search)
|--------------------------------------------------------------------------
| Each item: label, route (named, must exist), icon (FA solid),
| keywords[], permission (?string; null = no restriction), mode, group,
| anchor (settings tab id, else null).
|
| mode controls visibility, mirroring the sidebar's simple/full gates:
|   'both'   — always searchable
|   'simple' — only when SettingService::isSimpleMode() is true
|   'full'   — only when in full (advanced) accounting mode
|
| Kept in sync with Modules/Core/resources/views/partials/sidebar.blade.php
| by tests/Feature/NavigationSearchSyncTest.php.
*/

return [

    // ── Workspace ──
    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'keywords' => ['home', 'overview', 'main'], 'permission' => null, 'mode' => 'both', 'group' => 'Workspace', 'anchor' => null],

    // ── Sales ──
    ['label' => 'Sales List', 'route' => 'sales.index', 'icon' => 'fa-chart-line', 'keywords' => ['sale list', 'invoices', 'sell'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Create Order', 'route' => 'sales.create', 'icon' => 'fa-plus', 'keywords' => ['new sale', 'create order', 'new order'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Quotations', 'route' => 'quotations.index', 'icon' => 'fa-file-lines', 'keywords' => ['quote', 'estimate', 'proforma'], 'permission' => 'quotations.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],
    ['label' => 'Sales Returns', 'route' => 'sale-returns.index', 'icon' => 'fa-rotate-left', 'keywords' => ['return sale', 'refund'], 'permission' => 'sales.view', 'mode' => 'both', 'group' => 'Sales', 'anchor' => null],

    // ── Customers ──
    ['label' => 'Customers List', 'route' => 'customers.index', 'icon' => 'fa-users', 'keywords' => ['buyer', 'client'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Ledger', 'route' => 'customers.ledger', 'icon' => 'fa-book-open', 'keywords' => ['statement', 'balance'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Advances', 'route' => 'customers.advances', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['advance', 'prepaid'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Customer Groups', 'route' => 'customer-groups.index', 'icon' => 'fa-users-rectangle', 'keywords' => ['segment'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],
    ['label' => 'Areas', 'route' => 'customer-areas.index', 'icon' => 'fa-map-marker-alt', 'keywords' => ['zone', 'region', 'location'], 'permission' => 'customers.view', 'mode' => 'both', 'group' => 'Customers', 'anchor' => null],

    // ── Catalog ──
    ['label' => 'Products List', 'route' => 'products.index', 'icon' => 'fa-boxes-stacked', 'keywords' => ['items', 'inventory'], 'permission' => 'products.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Add Product', 'route' => 'products.create', 'icon' => 'fa-plus', 'keywords' => ['new product', 'create product'], 'permission' => 'products.create', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Categories', 'route' => 'categories.index', 'icon' => 'fa-tags', 'keywords' => ['product categories'], 'permission' => 'categories.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Brands', 'route' => 'brands.index', 'icon' => 'fa-copyright', 'keywords' => ['manufacturers'], 'permission' => 'brands.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Units', 'route' => 'units.index', 'icon' => 'fa-ruler', 'keywords' => ['uom', 'measurement'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Variants', 'route' => 'variants.index', 'icon' => 'fa-swatchbook', 'keywords' => ['size', 'color', 'attributes'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Print Barcode', 'route' => 'barcode.index', 'icon' => 'fa-barcode', 'keywords' => ['label', 'sticker'], 'permission' => null, 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Overview', 'route' => 'inventory.index', 'icon' => 'fa-cubes-stacked', 'keywords' => ['stock', 'quantity', 'overview'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Alerts', 'route' => 'inventory.alerts', 'icon' => 'fa-bell', 'keywords' => ['low stock', 'reorder'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],
    ['label' => 'Stock Adjustments', 'route' => 'inventory.adjustments', 'icon' => 'fa-sliders', 'keywords' => ['adjust stock', 'correction'], 'permission' => 'inventory.view', 'mode' => 'both', 'group' => 'Catalog', 'anchor' => null],

    // ── Purchasing ──
    ['label' => 'Purchase Orders', 'route' => 'purchases.index', 'icon' => 'fa-cart-plus', 'keywords' => ['po', 'buy', 'purchase order'], 'permission' => 'purchases.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Purchase Returns', 'route' => 'purchase-returns.index', 'icon' => 'fa-rotate-left', 'keywords' => ['return purchase'], 'permission' => 'purchases.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Suppliers List', 'route' => 'supplier.index', 'icon' => 'fa-truck-field', 'keywords' => ['vendor'], 'permission' => 'suppliers.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],
    ['label' => 'Supplier Groups', 'route' => 'supplier-groups.index', 'icon' => 'fa-layer-group', 'keywords' => ['supplier group'], 'permission' => 'suppliers.view', 'mode' => 'both', 'group' => 'Purchasing', 'anchor' => null],

    // ── Finance ──
    ['label' => 'Cash Flow', 'route' => 'money.cashflow', 'icon' => 'fa-right-left', 'keywords' => ['cash flow', 'cash movement'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Payment Accounts', 'route' => 'payment-accounts.index', 'icon' => 'fa-wallet', 'keywords' => ['cash', 'bank', 'bkash', 'nagad', 'account'], 'permission' => 'payments.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Investment', 'route' => 'investment.dashboard', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['investor', 'shareholder', 'capital', 'dividend'], 'permission' => 'accounting.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Expenses List', 'route' => 'expenses.index', 'icon' => 'fa-money-bill-wave', 'keywords' => ['cost', 'spend'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Expense Categories', 'route' => 'expense-categories.index', 'icon' => 'fa-tags', 'keywords' => ['expense category'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Assets', 'route' => 'assets.index', 'icon' => 'fa-building', 'keywords' => ['fixed asset', 'equipment'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Asset Categories', 'route' => 'asset-categories.index', 'icon' => 'fa-tags', 'keywords' => ['asset category'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Loans', 'route' => 'loans.index', 'icon' => 'fa-hand-holding-dollar', 'keywords' => ['borrow', 'lending'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],
    ['label' => 'Lenders', 'route' => 'lenders.index', 'icon' => 'fa-landmark', 'keywords' => ['lender', 'creditor'], 'permission' => 'finance.view', 'mode' => 'both', 'group' => 'Finance', 'anchor' => null],

    // ── Accounting (full mode only) ──
    ['label' => 'Journal Entries', 'route' => 'accounting.journal-entries', 'icon' => 'fa-book', 'keywords' => ['journal', 'debit credit', 'double entry'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'General Ledger', 'route' => 'accounting.general-ledger', 'icon' => 'fa-book-open', 'keywords' => ['gl', 'ledger'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Trial Balance', 'route' => 'accounting.trial-balance', 'icon' => 'fa-scale-balanced', 'keywords' => ['tb'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Profit & Loss', 'route' => 'accounting.profit-loss', 'icon' => 'fa-chart-pie', 'keywords' => ['pnl', 'income statement'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Balance Sheet', 'route' => 'accounting.balance-sheet', 'icon' => 'fa-scale-balanced', 'keywords' => ['bs', 'financial position'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Cash Flow Statement', 'route' => 'accounting.cash-flow', 'icon' => 'fa-water', 'keywords' => ['cash flow statement'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Credit Notes', 'route' => 'accounting.credit-notes.index', 'icon' => 'fa-file-circle-minus', 'keywords' => ['credit note', 'cn'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Debit Notes', 'route' => 'accounting.debit-notes.index', 'icon' => 'fa-file-circle-plus', 'keywords' => ['debit note', 'dn'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],
    ['label' => 'Receipts', 'route' => 'accounting.receipts.index', 'icon' => 'fa-file-invoice-dollar', 'keywords' => ['receipt', 'voucher'], 'permission' => 'accounting.view', 'mode' => 'full', 'group' => 'Accounting', 'anchor' => null],

    // ── Online Store ──
    ['label' => 'Campaigns', 'route' => 'ecommerce.campaigns.index', 'icon' => 'fa-bullhorn', 'keywords' => ['campaign'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Coupons', 'route' => 'ecommerce.coupons', 'icon' => 'fa-ticket', 'keywords' => ['discount code', 'promo'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Flash Deals', 'route' => 'ecommerce.flash-deals', 'icon' => 'fa-bolt', 'keywords' => ['flash sale', 'limited offer'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Collections', 'route' => 'ecommerce.collections', 'icon' => 'fa-layer-group', 'keywords' => ['product collection'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Combo Packages', 'route' => 'ecommerce.combos.index', 'icon' => 'fa-box-open', 'keywords' => ['combo', 'package', 'bundle'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Banners', 'route' => 'ecommerce.banners', 'icon' => 'fa-image', 'keywords' => ['slider', 'hero'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Homepage Builder', 'route' => 'ecommerce.homepage-sections', 'icon' => 'fa-puzzle-piece', 'keywords' => ['homepage', 'storefront builder', 'sections'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Menus', 'route' => 'ecommerce.menus.index', 'icon' => 'fa-bars', 'keywords' => ['menu', 'navigation'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog', 'route' => 'ecommerce.blog', 'icon' => 'fa-pen-nib', 'keywords' => ['article', 'content'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog Categories', 'route' => 'ecommerce.blog-categories', 'icon' => 'fa-tags', 'keywords' => ['blog category'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Blog Comments', 'route' => 'ecommerce.blog-comments', 'icon' => 'fa-comments', 'keywords' => ['blog comment'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Shipping Zones', 'route' => 'ecommerce.shipping', 'icon' => 'fa-truck-fast', 'keywords' => ['shipping', 'delivery zone', 'courier'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],
    ['label' => 'Store Settings', 'route' => 'ecommerce.settings', 'icon' => 'fa-gear', 'keywords' => ['store config', 'ecommerce settings'], 'permission' => 'ecommerce.view', 'mode' => 'both', 'group' => 'Online', 'anchor' => null],

    // ── Reports ──
    ['label' => 'Daily Summary (DTS)', 'route' => 'reports.dts', 'icon' => 'fa-file-lines', 'keywords' => ['dts', 'daily trading', 'daily report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Monthly Summary', 'route' => 'reports.monthly-summary', 'icon' => 'fa-file-lines', 'keywords' => ['monthly report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Sales Reports', 'route' => 'reports.sales', 'icon' => 'fa-file-lines', 'keywords' => ['sales report', 'revenue report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Detail Sales Report', 'route' => 'reports.detail-sales', 'icon' => 'fa-file-lines', 'keywords' => ['detail sales'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Category-wise Report', 'route' => 'reports.category-wise', 'icon' => 'fa-file-lines', 'keywords' => ['category sales'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Inventory Reports', 'route' => 'reports.inventory', 'icon' => 'fa-file-lines', 'keywords' => ['stock report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Purchase Reports', 'route' => 'reports.purchase', 'icon' => 'fa-file-lines', 'keywords' => ['purchase report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Receivables Aging', 'route' => 'reports.receivables-aging', 'icon' => 'fa-file-lines', 'keywords' => ['receivable', 'aging', 'due report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Profit & Loss Report', 'route' => 'reports.profit-loss', 'icon' => 'fa-file-lines', 'keywords' => ['pnl report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Cash Movement', 'route' => 'reports.cash-movement', 'icon' => 'fa-file-lines', 'keywords' => ['cash movement'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Supplier Payments', 'route' => 'reports.supplier-payments', 'icon' => 'fa-file-lines', 'keywords' => ['supplier payment report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Customer Reports', 'route' => 'reports.customer', 'icon' => 'fa-file-lines', 'keywords' => ['customer report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'VAT / Tax Reports', 'route' => 'reports.tax', 'icon' => 'fa-file-lines', 'keywords' => ['vat', 'tax report', 'mushak'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Financial Reports', 'route' => 'reports.financial', 'icon' => 'fa-file-lines', 'keywords' => ['financial report'], 'permission' => 'reports.view', 'mode' => 'full', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Staff Reports', 'route' => 'reports.staff', 'icon' => 'fa-file-lines', 'keywords' => ['staff report'], 'permission' => 'reports.view', 'mode' => 'both', 'group' => 'Reports', 'anchor' => null],
    ['label' => 'Custom Report Builder', 'route' => 'reports.custom', 'icon' => 'fa-file-lines', 'keywords' => ['custom builder', 'report builder'], 'permission' => 'reports.view', 'mode' => 'full', 'group' => 'Reports', 'anchor' => null],

    // ── Marketing ──
    ['label' => 'Ad Spend', 'route' => 'adspend.index', 'icon' => 'fa-bullhorn', 'keywords' => ['ad spend', 'advertising'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'SMS Campaigns', 'route' => 'marketing.sms-campaigns', 'icon' => 'fa-comment-sms', 'keywords' => ['sms', 'bulk sms'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'Email Marketing', 'route' => 'marketing.email', 'icon' => 'fa-envelope', 'keywords' => ['email campaign', 'newsletter'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],
    ['label' => 'Loyalty Program', 'route' => 'marketing.loyalty', 'icon' => 'fa-medal', 'keywords' => ['loyalty', 'points', 'rewards'], 'permission' => 'marketing.view', 'mode' => 'both', 'group' => 'Marketing', 'anchor' => null],

    // ── HR ──
    ['label' => 'Employees', 'route' => 'employee.index', 'icon' => 'fa-id-badge', 'keywords' => ['staff', 'worker'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Attendance', 'route' => 'attendance.index', 'icon' => 'fa-fingerprint', 'keywords' => ['check in', 'check out'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Leave Management', 'route' => 'attendance.leave', 'icon' => 'fa-calendar-xmark', 'keywords' => ['leave', 'absence', 'vacation'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Leave Types', 'route' => 'attendance.leave-types.index', 'icon' => 'fa-list', 'keywords' => ['leave type'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Weekends & Holidays', 'route' => 'attendance.config', 'icon' => 'fa-calendar-days', 'keywords' => ['weekend', 'holiday'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],
    ['label' => 'Payroll', 'route' => 'payroll.index', 'icon' => 'fa-money-check-dollar', 'keywords' => ['salary', 'wage', 'payslip'], 'permission' => 'hr.view', 'mode' => 'both', 'group' => 'HR', 'anchor' => null],

    // ── Manufacturing ──
    ['label' => 'Manufacturing Dashboard', 'route' => 'manufacturing.dashboard', 'icon' => 'fa-gauge-high', 'keywords' => ['mfg dashboard', 'production dashboard'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Production Orders', 'route' => 'manufacturing.production-orders.index', 'icon' => 'fa-clipboard-list', 'keywords' => ['production', 'work order'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Purchase Orders (RM)', 'route' => 'manufacturing.rm-purchases.index', 'icon' => 'fa-cart-plus', 'keywords' => ['rm purchase', 'raw material purchase'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Raw Materials', 'route' => 'manufacturing.raw-materials.index', 'icon' => 'fa-scissors', 'keywords' => ['raw material', 'fabric'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Colors', 'route' => 'manufacturing.colors.index', 'icon' => 'fa-palette', 'keywords' => ['color'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Sizes', 'route' => 'manufacturing.sizes.index', 'icon' => 'fa-ruler-combined', 'keywords' => ['size'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Suppliers (RM)', 'route' => 'manufacturing.suppliers.index', 'icon' => 'fa-truck-field', 'keywords' => ['rm supplier'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Factories', 'route' => 'manufacturing.factories.index', 'icon' => 'fa-industry', 'keywords' => ['factory'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Catalogs', 'route' => 'manufacturing.catalogs.index', 'icon' => 'fa-book', 'keywords' => ['catalog', 'bom'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Damages', 'route' => 'manufacturing.damages.index', 'icon' => 'fa-triangle-exclamation', 'keywords' => ['damage'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'RM Wastes', 'route' => 'manufacturing.wastes.rm.index', 'icon' => 'fa-trash', 'keywords' => ['rm waste'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Product Wastes', 'route' => 'manufacturing.wastes.products.index', 'icon' => 'fa-trash', 'keywords' => ['product waste'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'RM Stock Report', 'route' => 'manufacturing.reports.rm-stock', 'icon' => 'fa-file-lines', 'keywords' => ['rm stock report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Production Report', 'route' => 'manufacturing.reports.production', 'icon' => 'fa-file-lines', 'keywords' => ['production report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Damage Report', 'route' => 'manufacturing.reports.damage', 'icon' => 'fa-file-lines', 'keywords' => ['damage report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Waste Report', 'route' => 'manufacturing.reports.waste', 'icon' => 'fa-file-lines', 'keywords' => ['waste report'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],
    ['label' => 'Cost Analysis', 'route' => 'manufacturing.reports.cost-analysis', 'icon' => 'fa-file-lines', 'keywords' => ['cost analysis'], 'permission' => null, 'mode' => 'both', 'group' => 'Manufacturing', 'anchor' => null],

    // ── System ──
    ['label' => 'Locations', 'route' => 'locations.index', 'icon' => 'fa-location-dot', 'keywords' => ['location', 'warehouse', 'outlet'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Printers', 'route' => 'settings.printers.index', 'icon' => 'fa-print', 'keywords' => ['printer'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'General Settings', 'route' => 'settings.index', 'icon' => 'fa-gears', 'keywords' => ['settings', 'configuration', 'setup', 'general'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Email Config', 'route' => 'settings.email', 'icon' => 'fa-envelope', 'keywords' => ['email config', 'smtp'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Sidebar Menu', 'route' => 'settings.sidebar', 'icon' => 'fa-bars', 'keywords' => ['sidebar menu', 'menu config'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'AI Assistant', 'route' => 'admin.ai-assistant.settings', 'icon' => 'fa-robot', 'keywords' => ['ai assistant', 'ai'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'User Management', 'route' => 'security.users.index', 'icon' => 'fa-user-gear', 'keywords' => ['user management', 'admin', 'users'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Roles & Permissions', 'route' => 'security.roles', 'icon' => 'fa-shield-halved', 'keywords' => ['roles', 'permissions', 'access control'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Backup & Restore', 'route' => 'security.backup', 'icon' => 'fa-database', 'keywords' => ['backup', 'restore'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'API Management', 'route' => 'security.api-keys', 'icon' => 'fa-key', 'keywords' => ['api', 'api keys', 'token'], 'permission' => 'users.view', 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'Activity Log', 'route' => 'activities.index', 'icon' => 'fa-clock-rotate-left', 'keywords' => ['activity', 'audit log', 'history'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],
    ['label' => 'System Logs', 'route' => 'settings.system-logs.index', 'icon' => 'fa-terminal', 'keywords' => ['system log', 'logs'], 'permission' => null, 'mode' => 'both', 'group' => 'System', 'anchor' => null],

    // ── Settings tabs (deep-link to settings.index#anchor) ──
    ['label' => 'Business Profile', 'route' => 'settings.index', 'icon' => 'fa-building', 'keywords' => ['company', 'profile', 'business'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'businessSettings'],
    ['label' => 'Tax / VAT', 'route' => 'settings.index', 'icon' => 'fa-percent', 'keywords' => ['vat', 'tax', 'mushak', 'nbr'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'taxSettings'],
    ['label' => 'Courier & Delivery', 'route' => 'settings.index', 'icon' => 'fa-truck-fast', 'keywords' => ['courier', 'delivery', 'pathao', 'steadfast', 'redx'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'courierSettings'],
    ['label' => 'Invoice & Receipt', 'route' => 'settings.index', 'icon' => 'fa-file-invoice', 'keywords' => ['invoice', 'receipt', 'print format'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'invoiceSettings'],
    ['label' => 'Notifications', 'route' => 'settings.index', 'icon' => 'fa-bell', 'keywords' => ['notification', 'alert'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'notificationSettings'],
    ['label' => 'Localization', 'route' => 'settings.index', 'icon' => 'fa-language', 'keywords' => ['locale', 'language', 'timezone', 'currency'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'localeSettings'],
    ['label' => 'SMS Gateway', 'route' => 'settings.index', 'icon' => 'fa-comment-sms', 'keywords' => ['sms gateway', 'bulksmsbd'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'smsSettings'],
    ['label' => 'Tracking & Analytics', 'route' => 'settings.index', 'icon' => 'fa-chart-line', 'keywords' => ['tracking', 'analytics', 'pixel', 'gtag'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'trackingSettings'],
    ['label' => 'Webhooks', 'route' => 'settings.index', 'icon' => 'fa-link', 'keywords' => ['webhook', 'integration'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'webhookSettings'],
    ['label' => 'Landing Page Settings', 'route' => 'settings.index', 'icon' => 'fa-image', 'keywords' => ['landing page'], 'permission' => null, 'mode' => 'both', 'group' => 'Settings', 'anchor' => 'landingPageSettings'],

];
