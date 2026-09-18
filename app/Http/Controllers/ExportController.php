<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\AssetsExport;
use App\Exports\CreditNotesExport;
use App\Exports\CustomersExport;
use App\Exports\DebitNotesExport;
use App\Exports\DeliveryExport;
use App\Exports\EmployeesExport;
use App\Exports\ExpenseLedgerExport;
use App\Exports\ExpensesExport;
use App\Exports\InstallmentsExport;
use App\Exports\JournalEntriesExport;
use App\Exports\PaymentsExport;
use App\Exports\PayrollExport;
use App\Exports\ProductsExport;
use App\Exports\PurchaseReturnsExport;
use App\Exports\PurchasesExport;
use App\Exports\QuotationsExport;
use App\Exports\PayablesExport;
use App\Exports\ReceivablesExport;
use App\Exports\SaleReturnsExport;
use App\Exports\SalesExport;
use App\Exports\StockAdjustmentsExport;
use App\Exports\StockExport;
use App\Exports\StockLedgerExport;
use App\Exports\SuppliersExport;
use App\Http\Controllers\Concerns\ResolvesModulePermission;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends Controller
{
    use ResolvesModulePermission;
    /**
     * Export data for a given module in the requested format.
     */
    public function export(Request $request, string $module)
    {
        bpAuthorize($this->permissionGroupForModule($module) . '.export');
        $format = $request->input('format', 'xlsx');
        $filters = $request->except(['format']);

        $export = match ($module) {
            'sales'             => new SalesExport($filters),
            'products'          => new ProductsExport($filters),
            'purchases'         => new PurchasesExport($filters),
            'customers'         => new CustomersExport($filters),
            'expenses'          => new ExpensesExport($filters),
            'expense-ledger'    => new ExpenseLedgerExport($filters),
            'stock'             => new StockExport($filters),
            'employees'         => new EmployeesExport(),
            'payroll'           => new PayrollExport($filters['month'] ?? ''),
            'assets'            => new AssetsExport(),
            'suppliers'         => new SuppliersExport(),
            'receivables'       => new ReceivablesExport(),
            'payables'          => new PayablesExport($filters['search'] ?? null, isset($filters['supplier_group_id']) ? (int) $filters['supplier_group_id'] : null),
            'payments'          => new PaymentsExport($filters),
            'sale-returns'      => new SaleReturnsExport($filters),
            'purchase-returns'  => new PurchaseReturnsExport($filters),
            'installments'      => new InstallmentsExport($filters),
            'quotations'        => new QuotationsExport($filters),
            'delivery'          => new DeliveryExport($filters),
            'journal-entries'   => new JournalEntriesExport($filters),
            'credit-notes'      => new CreditNotesExport($filters),
            'debit-notes'       => new DebitNotesExport($filters),
            'stock-adjustments' => new StockAdjustmentsExport($filters),
            'stock-ledger'      => new StockLedgerExport($filters),
            'personal-loan-ledger' => new \App\Exports\PersonalLoanLedgerExport($filters),
            default             => abort(404, "Export not available for module: {$module}"),
        };

        $filename = $module . '-' . now()->format('Y-m-d');

        return match ($format) {
            'csv'   => Excel::download($export, "{$filename}.csv", \Maatwebsite\Excel\Excel::CSV),
            'pdf'   => $this->exportPdf($export, $filename, $module),
            'print' => $this->printTable($export, $module),
            default => Excel::download($export, "{$filename}.xlsx"),
        };
    }

    /**
     * Resolve an export into [$headings, $rows] for the PDF/print table views.
     *
     * FromCollection exports (e.g. ExpensesExport) already return pre-mapped
     * rows including any totals row, so use collection() directly. FromQuery
     * exports are streamed and mapped on the fly (capped at 500 rows to keep the
     * document manageable).
     */
    private function resolveTableData($export): array
    {
        $headings = $export->headings();

        if ($export instanceof \Maatwebsite\Excel\Concerns\FromCollection) {
            return [$headings, $export->collection()];
        }

        $rows = $export->query()->limit(500)->get()->map(fn ($item) => $export->map($item));

        return [$headings, $rows];
    }

    /**
     * Generate PDF export using dompdf with table layout.
     */
    private function exportPdf($export, string $filename, string $module)
    {
        [$headings, $rows] = $this->resolveTableData($export);
        $title = ucfirst($module) . ' Report';

        $pdf = Pdf::loadView('exports.table-pdf', compact('title', 'headings', 'rows'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("{$filename}.pdf");
    }

    /**
     * Render a printable HTML table that auto-opens the browser print dialog.
     * Shares the same data resolution as the PDF export.
     */
    private function printTable($export, string $module)
    {
        [$headings, $rows] = $this->resolveTableData($export);
        $title = ucfirst($module) . ' Report';

        return response()->view('exports.table-print', compact('title', 'headings', 'rows'));
    }
}
