<?php

namespace Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryStructureRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $structureId = $this->route('salary_structure')?->id ?? $this->route('salaryStructure')?->id;

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:salary_structures,code,' . $structureId,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'components' => 'nullable|array',
            'components.*.name' => 'required_with:components|string|max:255',
            'components.*.type' => 'required_with:components|in:earning,deduction',
            'components.*.calculation_type' => 'required_with:components|in:fixed,percentage',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0|max:100',
            'components.*.percentage_of' => 'nullable|in:basic,gross',
        ];
    }
}
