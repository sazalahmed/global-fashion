<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Report\Http\Requests\CustomReportRequest;
use Modules\Report\Http\Requests\CustomerReportRequest;
use Modules\Report\Http\Requests\DateRangeRequest;
use Modules\Report\Http\Requests\FinancialReportRequest;
use Modules\Report\Http\Requests\InventoryReportRequest;
use Modules\Report\Http\Requests\PurchaseReportRequest;
use Modules\Report\Http\Requests\SalesReportRequest;
use Modules\Report\Http\Requests\StaffReportRequest;
use Modules\Report\Services\CustomerReportService;
use Modules\Report\Services\DTSReportService;
use Modules\Report\Services\FinancialReportService;
use Modules\Report\Services\InventoryReportService;
use Modules\Report\Services\PurchaseReportService;
use Modules\Report\Services\SalesReportService;
use Modules\Report\Services\AdditionalReportService;
use Modules\Report\Services\StaffReportService;

class ReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $salesReport,
        private readonly PurchaseReportService $purchaseReport,
        private readonly InventoryReportService $inventoryReport,
        private readonly FinancialReportService $financialReport,
        private readonly CustomerReportService $customerReport,
        private readonly StaffReportService $staffReport,
        private readonly DTSReportService $dtsReport,
        private readonly AdditionalReportService $additionalReport,
    ) {}

    public function index()
    {
        bpAuthorize('reports.view');
        return view('report::index');
    }

    /**
     * Daily Transaction Summary.
     */
    public function dts(DateRangeRequest $request)
    {
        bpAuthorize('reports.view');
        $date = $request->input('date', now()->toDateString());
        $summary = $this->dtsReport->getSummary($date);

        return view('report::dts', compact('summary', 'date'));
    }

    public function categoryWise(DateRangeRequest $request)
    {
        bpAuthorize('reports.view');
        $result = $this->additionalReport->categorySales($request->only(['from_date', 'to_date']));
        return view('report::category-wise', $result);
    }

    public function monthlySummary(FinancialReportRequest $request)
    {
        bpAuthorize('reports.view');
        $result = $this->additionalReport->monthlySummary($request->only(['year']));
        return view('report::monthly-summary', $result);
    }

    public function detailSales(CustomerReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date', 'customer_id']);
        $result = $this->additionalReport->detailSales($filters);

        // Echo the applied range back so the date inputs always show it,
        // including the default (month start → today) when none was given.
        $filters['from_date'] = $result['from'];
        $filters['to_date'] = $result['to'];

        $customers = \Modules\Customer\Models\Customer::orderBy('name')->get(['id', 'name']);
        return view('report::detail-sales', array_merge($result, compact('customers', 'filters')));
    }

    public function receivablesAging()
    {
        bpAuthorize('reports.view');
        $result = $this->additionalReport->receivablesAging();
        return view('report::receivables-aging', $result);
    }

    public function cashMovement(DateRangeRequest $request)
    {
        bpAuthorize('reports.view');
        $result = $this->additionalReport->cashMovement($request->only(['from_date', 'to_date']));
        return view('report::cash-movement', $result);
    }

    public function supplierPayments(DateRangeRequest $request)
    {
        bpAuthorize('reports.view');
        $result = $this->additionalReport->supplierPayments($request->only(['from_date', 'to_date']));
        return view('report::supplier-payments', $result);
    }

    public function sales(SalesReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date', 'branch_id', 'report_type', 'year']);
        $reportType = $filters['report_type'] ?? 'daily';
        $summary = $this->salesReport->getSummary($filters);

        $data = match ($reportType) {
            'monthly' => $this->salesReport->monthlySales($filters),
            'by_product' => $this->salesReport->salesByProduct($filters),
            'by_customer' => $this->salesReport->salesByCustomer($filters),
            'by_branch' => $this->salesReport->salesByBranch($filters),
            default => $this->salesReport->dailySales($filters),
        };

        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();

        return view('report::sales', compact('data', 'summary', 'reportType', 'branches', 'filters'));
    }

    public function purchase(PurchaseReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date', 'branch_id', 'report_type']);
        $reportType = $filters['report_type'] ?? 'daily';
        $summary = $this->purchaseReport->getSummary($filters);

        $data = match ($reportType) {
            'by_supplier' => $this->purchaseReport->purchaseBySupplier($filters),
            'by_product' => $this->purchaseReport->purchaseByProduct($filters),
            default => $this->purchaseReport->dailyPurchases($filters),
        };

        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();

        return view('report::purchase', compact('data', 'summary', 'reportType', 'branches', 'filters'));
    }

    public function inventory(InventoryReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['product_id', 'source_type', 'from_date', 'to_date', 'low_stock', 'report_type']);
        $reportType = $filters['report_type'] ?? 'summary';
        $summary = $this->inventoryReport->getSummary();

        $data = match ($reportType) {
            'movement' => $this->inventoryReport->stockMovement($filters),
            'low_stock' => $this->inventoryReport->lowStockReport(),
            default => $this->inventoryReport->stockSummary($filters),
        };

        $products = \Modules\Product\Models\Product::orderBy('name')->get(['id', 'name', 'sku']);

        return view('report::inventory', compact('data', 'summary', 'reportType', 'products', 'filters'));
    }

    public function financial(FinancialReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date', 'year', 'report_type']);
        $reportType = $filters['report_type'] ?? 'summary';

        $data = match ($reportType) {
            'profit_trend' => $this->financialReport->profitTrend($filters),
            'account_balances' => $this->financialReport->accountBalances(),
            default => null,
        };

        $summary = $this->financialReport->incomeExpenseSummary($filters);
        $profitTrend = $this->financialReport->profitTrend($filters);

        return view('report::financial', compact('data', 'summary', 'profitTrend', 'reportType', 'filters'));
    }

    public function profitLoss(\Modules\Report\Http\Requests\DateRangeRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date']);
        $filters['from_date'] = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $filters['to_date']   = $filters['to_date'] ?? now()->endOfMonth()->toDateString();

        $report = $this->financialReport->profitAndLoss($filters);

        return view('report::profit-loss', compact('report', 'filters'));
    }

    public function staff(StaffReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'department', 'status']);
        $data = $this->staffReport->getReport($filters);
        $stats = $this->staffReport->getStats($filters);
        $departments = $this->staffReport->getDepartments();
        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();

        return view('report::staff', compact('data', 'stats', 'departments', 'branches', 'filters'));
    }

    public function customer(CustomerReportRequest $request)
    {
        bpAuthorize('reports.view');
        $filters = $request->only(['from_date', 'to_date', 'customer_id', 'report_type', 'limit']);
        $reportType = $filters['report_type'] ?? 'top';

        $data = match ($reportType) {
            'ledger' => $filters['customer_id'] ? $this->customerReport->customerLedger((int) $filters['customer_id'], $filters) : collect(),
            'aging' => $this->customerReport->agingReport(),
            default => $this->customerReport->topCustomers($filters),
        };

        $customers = \Modules\Customer\Models\Customer::orderBy('name')->get(['id', 'name', 'phone']);

        return view('report::customer', compact('data', 'reportType', 'customers', 'filters'));
    }

    /**
     * Export a report as PDF — reuses the same view with dompdf.
     */
    public function exportPdf(Request $request, string $type)
    {
        bpAuthorize('reports.export');
        $format = $request->input('format', 'pdf');

        // Build the same view data as the corresponding report method
        $viewData = match ($type) {
            'sales' => $this->buildSalesViewData($request),
            'purchase' => $this->buildPurchaseViewData($request),
            'financial' => $this->buildFinancialViewData($request),
            'inventory' => $this->buildInventoryViewData($request),
            'staff' => $this->buildStaffViewData($request),
            'customer' => $this->buildCustomerViewData($request),
            default => abort(404),
        };

        $viewName = "report::{$type}";
        $filename = ucfirst($type) . '-Report-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($viewName, array_merge($viewData, ['isPdf' => true]))
                ->setPaper('a4', 'landscape');
            return $pdf->download("{$filename}.pdf");
        }

        // For Excel, use a generic collection export
        if ($format === 'xlsx' || $format === 'csv') {
            $data = $viewData['data'] ?? collect();
            $export = new \App\Exports\GenericCollectionExport($data, ucfirst($type) . ' Report');
            $ext = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
            return \Maatwebsite\Excel\Facades\Excel::download($export, "{$filename}.{$format}", $ext);
        }

        return abort(400, 'Unsupported format');
    }

    private function buildSalesViewData(Request $request): array
    {
        $filters = $request->only(['from_date', 'to_date', 'branch_id', 'report_type', 'year']);
        $reportType = $filters['report_type'] ?? 'daily';
        $summary = $this->salesReport->getSummary($filters);
        $data = match ($reportType) {
            'monthly' => $this->salesReport->monthlySales($filters),
            'by_product' => $this->salesReport->salesByProduct($filters),
            'by_customer' => $this->salesReport->salesByCustomer($filters),
            'by_branch' => $this->salesReport->salesByBranch($filters),
            default => $this->salesReport->dailySales($filters),
        };
        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();
        return compact('data', 'summary', 'reportType', 'branches', 'filters');
    }

    private function buildPurchaseViewData(Request $request): array
    {
        $filters = $request->only(['from_date', 'to_date', 'branch_id', 'report_type']);
        $reportType = $filters['report_type'] ?? 'daily';
        $summary = $this->purchaseReport->getSummary($filters);
        $data = match ($reportType) {
            'by_supplier' => $this->purchaseReport->purchaseBySupplier($filters),
            'by_product' => $this->purchaseReport->purchaseByProduct($filters),
            default => $this->purchaseReport->dailyPurchases($filters),
        };
        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();
        return compact('data', 'summary', 'reportType', 'branches', 'filters');
    }

    private function buildFinancialViewData(Request $request): array
    {
        $filters = $request->only(['from_date', 'to_date', 'report_type']);
        $reportType = $filters['report_type'] ?? 'summary';
        $summary = $this->financialReport->getSummary($filters);
        $data = match ($reportType) {
            'income_expense' => $this->financialReport->incomeExpenseDetails($filters),
            default => $this->financialReport->getOverview($filters),
        };
        $profitTrend = $this->financialReport->profitTrend($filters);
        return compact('data', 'summary', 'profitTrend', 'reportType', 'filters');
    }

    private function buildInventoryViewData(Request $request): array
    {
        $filters = $request->only(['search', 'stock_status', 'report_type']);
        $reportType = $filters['report_type'] ?? 'stock_levels';
        $data = $this->inventoryReport->getReport($filters);
        $stats = $this->inventoryReport->getStats();
        return compact('data', 'stats', 'reportType', 'filters');
    }

    private function buildStaffViewData(Request $request): array
    {
        $filters = $request->only(['date_from', 'date_to', 'branch_id', 'department', 'status']);
        $data = $this->staffReport->getReport($filters);
        $stats = $this->staffReport->getStats($filters);
        $departments = $this->staffReport->getDepartments();
        $branches = \Modules\Branch\Models\Branch::where('is_active', true)->get();
        return compact('data', 'stats', 'departments', 'branches', 'filters');
    }

    private function buildCustomerViewData(Request $request): array
    {
        $filters = $request->only(['from_date', 'to_date', 'customer_id', 'report_type', 'limit']);
        $reportType = $filters['report_type'] ?? 'top';
        $data = match ($reportType) {
            'ledger' => !empty($filters['customer_id']) ? $this->customerReport->customerLedger((int) $filters['customer_id'], $filters) : collect(),
            'aging' => $this->customerReport->agingReport(),
            default => $this->customerReport->topCustomers($filters),
        };
        $customers = \Modules\Customer\Models\Customer::orderBy('name')->get(['id', 'name', 'phone']);
        return compact('data', 'reportType', 'customers', 'filters');
    }

    public function custom()
    {
        bpAuthorize('reports.view');
        return view('report::custom');
    }

    /**
     * Generate custom report data (AJAX).
     */
    public function customGenerate(CustomReportRequest $request)
    {
        bpAuthorize('reports.view');
        $reportType = $request->input('report_type');

        // Map date_from/date_to to from_date/to_date for service compatibility
        $filters = [
            'from_date'  => $request->input('date_from'),
            'to_date'    => $request->input('date_to'),
            'branch_id'  => $request->input('branch_id'),
        ];

        $result = match ($reportType) {
            'sales'     => $this->buildSalesReport($filters),
            'purchase'  => $this->buildPurchaseReport($filters),
            'inventory' => $this->buildInventoryReport($filters),
            'financial' => $this->buildFinancialReport($filters),
        };

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    private function buildSalesReport(array $filters): array
    {
        $summary = $this->salesReport->getSummary($filters);
        $rows = $this->salesReport->dailySales($filters);

        return [
            'stats' => [
                ['label' => 'Total Invoices', 'value' => $summary['total_invoices'], 'icon' => 'fa-chart-line', 'color' => 'primary'],
                ['label' => 'Revenue', 'value' => $summary['total_sales'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'success', 'is_currency' => true],
                ['label' => 'Total Collected', 'value' => $summary['total_collected'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'info', 'is_currency' => true],
                ['label' => 'Total Due', 'value' => $summary['total_due'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'danger', 'is_currency' => true],
            ],
            'rows' => $rows->map(function ($row) {
                return [
                    'date'           => $row->date,
                    'invoice'        => $row->total_invoices . ' invoices',
                    'customer'       => '-',
                    'items'          => '-',
                    'subtotal'       => (float) $row->total_sales,
                    'discount'       => (float) $row->total_discount,
                    'tax'            => (float) $row->total_tax,
                    'total'          => (float) $row->total_sales,
                    'payment_method' => '-',
                    'payment_status' => '-',
                    'branch'         => '-',
                    'salesperson'    => '-',
                ];
            })->values(),
            'chart' => [
                'labels' => $rows->pluck('date')->values(),
                'data'   => $rows->pluck('total_sales')->map(fn ($v) => (float) $v)->values(),
                'label'  => 'Sales (' . currency_symbol() . ')',
            ],
        ];
    }

    private function buildPurchaseReport(array $filters): array
    {
        $summary = $this->purchaseReport->getSummary($filters);
        $rows = $this->purchaseReport->dailyPurchases($filters);

        return [
            'stats' => [
                ['label' => 'Total Bills', 'value' => $summary['total_bills'], 'icon' => 'fa-cart-shopping', 'color' => 'primary'],
                ['label' => 'Total Spend', 'value' => $summary['total_purchases'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'danger', 'is_currency' => true],
                ['label' => 'Total Paid', 'value' => $summary['total_paid'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'success', 'is_currency' => true],
                ['label' => 'Total Due', 'value' => $summary['total_due'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'warning', 'is_currency' => true],
            ],
            'rows' => $rows->map(function ($row) {
                return [
                    'date'           => $row->date,
                    'po_number'      => $row->total_bills . ' bills',
                    'supplier'       => '-',
                    'items'          => '-',
                    'total'          => (float) $row->total_purchases,
                    'paid'           => (float) $row->total_paid,
                    'due'            => (float) $row->total_due,
                    'status'         => '-',
                    'branch'         => '-',
                    'payment_method' => '-',
                ];
            })->values(),
            'chart' => [
                'labels' => $rows->pluck('date')->values(),
                'data'   => $rows->pluck('total_purchases')->map(fn ($v) => (float) $v)->values(),
                'label'  => 'Purchases (' . currency_symbol() . ')',
            ],
        ];
    }

    private function buildInventoryReport(array $filters): array
    {
        $summary = $this->inventoryReport->getSummary();
        $rows = $this->inventoryReport->stockSummary($filters);

        // Aggregate stock value by product name for chart (top 10)
        $chartData = $rows->groupBy('name')->map(function ($group) {
            return $group->sum('stock_value');
        })->sortDesc()->take(10);

        return [
            'stats' => [
                ['label' => 'Total Products', 'value' => $summary['total_products'], 'icon' => 'fa-boxes-stacked', 'color' => 'primary'],
                ['label' => 'Stock Value', 'value' => $summary['total_stock_value'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'success', 'is_currency' => true],
                ['label' => 'Low Stock Items', 'value' => $summary['low_stock_count'], 'icon' => 'fa-triangle-exclamation', 'color' => 'warning'],
                ['label' => 'Out of Stock', 'value' => $summary['out_of_stock'], 'icon' => 'fa-xmark-circle', 'color' => 'danger'],
            ],
            'rows' => $rows->map(function ($row) {
                $status = 'In Stock';
                if ((int) $row->quantity <= 0) {
                    $status = 'Out of Stock';
                } elseif ($row->reorder_level > 0 && (int) $row->quantity <= (int) $row->reorder_level) {
                    $status = 'Low Stock';
                }

                return [
                    'product'    => $row->name,
                    'sku'        => $row->sku,
                    'category'   => '-',
                    'branch'     => '-',
                    'in_stock'   => (int) $row->quantity,
                    'min_stock'  => (int) $row->reorder_level,
                    'cost_price' => '-',
                    'sell_price' => '-',
                    'stock_value' => (float) $row->stock_value,
                    'status'     => $status,
                ];
            })->values(),
            'chart' => [
                'labels' => $chartData->keys()->values(),
                'data'   => $chartData->values()->values(),
                'label'  => 'Stock Value (' . currency_symbol() . ')',
            ],
        ];
    }

    private function buildFinancialReport(array $filters): array
    {
        $summary = $this->financialReport->incomeExpenseSummary($filters);
        $profitTrend = $this->financialReport->profitTrend($filters);

        // Filter out months with zero activity
        $activeMonths = $profitTrend->filter(fn ($m) => $m['sales'] > 0 || $m['expenses'] > 0 || $m['purchases'] > 0);

        return [
            'stats' => [
                ['label' => 'Total Income', 'value' => $summary['total_income'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'success', 'is_currency' => true],
                ['label' => 'Gross Profit', 'value' => $summary['gross_profit'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'primary', 'is_currency' => true],
                ['label' => 'Net Profit', 'value' => $summary['net_profit'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'info', 'is_currency' => true],
                ['label' => 'Total Expenses', 'value' => $summary['total_expenses'], 'icon' => 'fa-bangladeshi-taka-sign', 'color' => 'danger', 'is_currency' => true],
            ],
            'rows' => $profitTrend->map(function ($row) {
                $margin = $row['sales'] > 0 ? round(($row['profit'] / $row['sales']) * 100, 1) : 0;
                return [
                    'month'              => $row['month'],
                    'revenue'            => (float) $row['sales'],
                    'cogs'               => (float) $row['purchases'],
                    'gross_profit'       => (float) ($row['sales'] - $row['purchases']),
                    'operating_expenses' => (float) $row['expenses'],
                    'net_profit'         => (float) $row['profit'],
                    'margin'             => $margin . '%',
                    'tax_amount'         => '-',
                ];
            })->values(),
            'chart' => [
                'labels' => $profitTrend->pluck('month')->values(),
                'data'   => $profitTrend->pluck('profit')->map(fn ($v) => (float) $v)->values(),
                'label'  => 'Net Profit (' . currency_symbol() . ')',
            ],
        ];
    }
}
