<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payroll\Models\Payroll;
use Modules\Payroll\Services\PayrollService;

class PayrollApiController extends BaseApiController
{
    public function __construct(private readonly PayrollService $service) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->service->listPayrolls($request->all(), $request->input('per_page', 15)));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success($this->service->findPayroll($id));
    }

    public function store(Request $request): JsonResponse
    {
        $v = $request->validate(['month' => 'required|string', 'branch_id' => 'nullable|integer']);
        return $this->success($this->service->generatePayroll($v['month'], $v['branch_id'] ?? null), 'Payroll generated', 201);
    }

    public function approve(int $id): JsonResponse
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status !== 'draft') { return $this->error('Only draft payrolls can be approved', 422); }
        return $this->success($this->service->approvePayroll($payroll), 'Payroll approved');
    }

    public function pay(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::findOrFail($id);
        if ($payroll->status !== 'approved') { return $this->error('Only approved payrolls can be paid', 422); }
        $method = $request->input('payment_method', 'bank_transfer');
        return $this->success($this->service->markAsPaid($payroll, $method), 'Payroll marked as paid');
    }

    public function salaryStructures(): JsonResponse
    {
        return $this->success($this->service->listStructures());
    }
}
