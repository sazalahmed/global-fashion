<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Models\CustomerGroup;

class CustomerGroupApiController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $groups = CustomerGroup::withCount('customers')->orderBy('name')->get();

        return $this->success($groups, 'Customer groups retrieved');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $group = CustomerGroup::create($validated);

        return $this->success($group, 'Customer group created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $group = CustomerGroup::findOrFail($id);

        $validated = $request->validate([
            'name'                => 'sometimes|required|string|max:255',
            'description'         => 'nullable|string|max:500',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $group->update($validated);

        return $this->success($group, 'Customer group updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $group = CustomerGroup::findOrFail($id);
        $group->delete();

        return $this->success(null, 'Customer group deleted');
    }
}
