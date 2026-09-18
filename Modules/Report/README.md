# Report Module

## Overview
Centralized reporting engine for the entire system. Provides sales, purchase, inventory, financial, tax, staff, and customer reports with filtering, date ranges, and export capabilities. Includes the DTS (Daily Transaction Summary) report for Bangladesh compliance.

## Controllers
- **ReportController** — Handles all report views and data retrieval. Routes to the appropriate service based on report type. Supports filtering, date range selection, and export (PDF/Excel/CSV).

## Services
- **SalesReportService** — Sales summaries, detail sales, category-wise sales, monthly/daily breakdowns, top products, sales by staff.
- **PurchaseReportService** — Purchase summaries, supplier-wise purchases, purchase history, and cost analysis.
- **InventoryReportService** — Stock levels, stock movement, low-stock alerts, stock valuation, and dead stock identification.
- **FinancialReportService** — Profit & loss, cash movement, expense summaries, revenue analysis, and account balances.
- **TaxReportService** — VAT/tax collected, tax payable, Mushak-compliant reports for NBR Bangladesh.
- **StaffReportService** — Staff performance, sales by employee, attendance-based reports, and commission calculations.
- **CustomerReportService** — Customer purchase history, receivables aging, top customers, and customer group analysis.
- **AdditionalReportService** — Custom and miscellaneous reports not covered by the primary categories.
- **DTSReportService** — Daily Transaction Summary report required for Bangladesh retail compliance.

## Form Requests
- **DateRangeRequest** - Base request for date range and branch filters (reusable)
- **SalesReportRequest** - Extends DateRangeRequest, adds report_type and year
- **PurchaseReportRequest** - Extends DateRangeRequest, adds report_type
- **InventoryReportRequest** - Extends DateRangeRequest, adds warehouse, product, source_type, low_stock filters
- **FinancialReportRequest** - Extends DateRangeRequest, adds year and report_type
- **TaxReportRequest** - Extends DateRangeRequest, adds year
- **StaffReportRequest** - Standalone with date_from/date_to, department, status filters
- **CustomerReportRequest** - Extends DateRangeRequest, adds customer_id, report_type, limit
- **CustomReportRequest** - Validates custom report generation (required report_type, optional dates)

## Routes
| Method | URI | Description |
|--------|-----|-------------|
| GET | `/reports/dts` | Daily Transaction Summary |
| GET | `/reports/sales` | Sales reports dashboard |
| GET | `/reports/sales/detail` | Detailed sales report |
| GET | `/reports/sales/category-wise` | Category-wise sales |
| GET | `/reports/sales/monthly-summary` | Monthly sales summary |
| GET | `/reports/purchases` | Purchase reports |
| GET | `/reports/inventory` | Inventory reports |
| GET | `/reports/financial` | Financial reports |
| GET | `/reports/financial/cash-movement` | Cash movement report |
| GET | `/reports/tax` | Tax/VAT reports |
| GET | `/reports/staff` | Staff performance reports |
| GET | `/reports/customers` | Customer reports |
| GET | `/reports/customers/receivables-aging` | Receivables aging report |
| GET | `/reports/supplier-payments` | Supplier payment reports |

## Settings / Configuration
- Default date range for reports (today, this week, this month, custom).
- Report export formats enabled (PDF, Excel, CSV).
- Fiscal year start month for annual reports.
- DTS report configuration for Bangladesh compliance.

## Dependencies
- **Sale** — Sales data for sales and financial reports.
- **Purchase** — Purchase data for purchase and cost reports.
- **Inventory** — Stock data for inventory reports.
- **Customer** — Customer data for customer and receivables reports.
- **Supplier** — Supplier data for supplier payment reports.
- **Employee** — Staff data for performance and attendance reports.
- **Accounting** — Ledger and transaction data for financial reports.
- **Payment** — Payment records for cash movement and aging reports.
