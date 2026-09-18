<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\EcommerceOrder;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\Banner;
use Modules\Ecommerce\Services\EcommerceService;

class EcommerceApiController extends BaseApiController
{
    public function __construct(private readonly EcommerceService $service) {}

    public function dashboard(): JsonResponse
    {
        return $this->success($this->service->getOrderStats());
    }

    public function orderList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->service->listOrders($request->all(), $request->input('per_page', 15)));
    }

    public function orderShow(int $id): JsonResponse
    {
        return $this->success($this->service->findOrder($id));
    }

    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $v = $request->validate(['status' => 'required|in:pending,processing,shipped,delivered,cancelled']);
        $order = EcommerceOrder::findOrFail($id);
        $this->service->updateOrderStatus($order, $v['status']);
        return $this->success($order->fresh(), 'Order status updated');
    }

    public function couponList(Request $request): JsonResponse
    {
        return $this->paginatedSuccess($this->service->listCoupons($request->all(), $request->input('per_page', 15)));
    }

    public function couponStore(Request $request): JsonResponse
    {
        $v = $request->validate([
            'code' => 'required|string|max:50|unique:coupons', 'name' => 'nullable|string|max:255',
            'type' => 'required|in:fixed,percentage', 'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0', 'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1', 'start_date' => 'required|date', 'end_date' => 'required|date|after:start_date',
            'is_active' => 'nullable|boolean',
        ]);
        return $this->success($this->service->createCoupon($v), 'Coupon created', 201);
    }

    public function couponUpdate(Request $request, int $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);
        $v = $request->validate([
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $id, 'name' => 'nullable|string',
            'type' => 'sometimes|in:fixed,percentage', 'value' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric', 'max_discount_amount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer', 'start_date' => 'sometimes|date', 'end_date' => 'sometimes|date',
            'is_active' => 'nullable|boolean',
        ]);
        return $this->success($this->service->updateCoupon($coupon, $v));
    }

    public function couponDestroy(int $id): JsonResponse
    {
        $this->service->deleteCoupon(Coupon::findOrFail($id));
        return $this->success(null, 'Coupon deleted');
    }

    public function bannerList(): JsonResponse
    {
        return $this->success(Banner::orderBy('sort_order')->get());
    }

    public function bannerStore(Request $request): JsonResponse
    {
        $v = $request->validate(['title' => 'required|string|max:255', 'image' => 'required|image|max:2048', 'button_url' => 'nullable|string', 'position' => 'nullable|string', 'sort_order' => 'nullable|integer', 'is_active' => 'nullable|boolean']);
        if ($request->hasFile('image')) { $v['image'] = Upload::store($request->file('image'), 'banners'); }
        return $this->success(Banner::create($v), 'Banner created', 201);
    }

    public function bannerUpdate(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $v = $request->validate(['title' => 'sometimes|string|max:255', 'image' => 'nullable|image|max:2048', 'button_url' => 'nullable|string', 'sort_order' => 'nullable|integer', 'is_active' => 'nullable|boolean']);
        if ($request->hasFile('image')) { $v['image'] = Upload::store($request->file('image'), 'banners'); }
        $banner->update($v);
        return $this->success($banner->fresh());
    }

    public function bannerDestroy(int $id): JsonResponse
    {
        Banner::findOrFail($id)->delete();
        return $this->success(null, 'Banner deleted');
    }
}
