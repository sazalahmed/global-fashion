<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\ResolvesModulePermission;
use App\Imports\ProductsImport;
use App\Imports\CustomersImport;
use App\Imports\SuppliersImport;

class ImportController extends Controller
{
    use ResolvesModulePermission;
    /**
     * Import data from uploaded file for a given module.
     */
    public function import(Request $request, string $module)
    {
        bpAuthorize($this->permissionGroupForModule($module) . '.create');
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = match ($module) {
            'products'  => new ProductsImport(),
            'customers' => new CustomersImport(),
            'suppliers' => new SuppliersImport(),
            default     => abort(404, "Import not available for: {$module}"),
        };

        $import->import($request->file('file'));

        $errors = $import->errors();
        $count = $import->getImportedCount();

        if ($errors->isNotEmpty()) {
            return back()->with('warning', "{$count} records imported. " . $errors->count() . " rows had errors and were skipped.");
        }

        return back()->with('success', "{$count} records imported successfully.");
    }
}
