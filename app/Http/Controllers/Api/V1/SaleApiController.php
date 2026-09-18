<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SaleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sale\Services\SaleService;

class SaleApiController extends BaseApiController
{
    public function __construct(
        private readonly SaleService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sales = $this->service->list($request->all(), $request->input('per_page', 15));

        return $this->paginatedSuccess($sales, 'Sales retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $sale = $this->service->find($id);

        return $this->success(new SaleResource($sale), 'Sale retrieved');
    }
}
