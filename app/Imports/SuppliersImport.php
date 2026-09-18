<?php

namespace App\Imports;

use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierGroup;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;

class SuppliersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    private int $imported = 0;
    private array $groupCache = [];

    public function model(array $row): ?Supplier
    {
        // Skip if supplier with same phone already exists
        if (!empty($row['phone']) && Supplier::where('phone', $row['phone'])->exists()) {
            return null;
        }

        $this->imported++;

        return new Supplier([
            'company_name'      => $row['company_name'],
            'contact_person'    => $row['contact_person'] ?? $row['company_name'],
            'phone'             => $row['phone'] ?? null,
            'email'             => $row['email'] ?? null,
            'division'          => $row['division'] ?? null,
            'district'          => $row['district'] ?? null,
            'area'              => $row['area'] ?? null,
            'address'           => $row['address'] ?? null,
            'opening_balance'   => $row['opening_balance'] ?? 0,
            'credit_limit'      => $row['credit_limit'] ?? 0,
            'payment_terms'     => $row['payment_terms'] ?? 'Net 30',
            'supplier_group_id' => $this->resolveGroupId($row['supplier_group'] ?? null),
            'status'            => 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name'    => 'required|string|max:255',
            'phone'           => 'nullable|string|max:20',
            'email'           => 'nullable|email|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'credit_limit'    => 'nullable|numeric|min:0',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    private function resolveGroupId(?string $groupName): ?int
    {
        if (empty($groupName)) {
            return null;
        }

        $groupName = trim($groupName);

        if (!isset($this->groupCache[$groupName])) {
            $this->groupCache[$groupName] = SupplierGroup::where('name', $groupName)->value('id');
        }

        return $this->groupCache[$groupName];
    }
}
