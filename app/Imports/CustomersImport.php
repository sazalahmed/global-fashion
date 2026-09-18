<?php

namespace App\Imports;

use Modules\Customer\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\Importable;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use Importable, SkipsErrors;

    private int $imported = 0;

    public function model(array $row): ?Customer
    {
        // Skip if customer with same phone already exists
        if (!empty($row['phone']) && Customer::where('phone', $row['phone'])->exists()) {
            return null;
        }

        $this->imported++;

        return new Customer([
            'name'    => $row['name'],
            'phone'   => $row['phone'] ?? null,
            'email'   => $row['email'] ?? null,
            'address' => $row['address'] ?? null,
            'status'  => $row['status'] ?? 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }
}
