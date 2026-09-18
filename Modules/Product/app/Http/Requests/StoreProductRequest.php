<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Derive the primary category_id from the submitted categories[] array
     * so the existing required|exists rule keeps working.
     */
    protected function prepareForValidation(): void
    {
        $categories = array_values(array_filter((array) $this->input('categories', []), fn ($v) => $v !== null && $v !== ''));
        if (!empty($categories) && empty($this->input('category_id'))) {
            // Primary = the most specific (deepest) selected category, so a
            // chosen child wins over its auto-selected parent.
            $this->merge(['category_id' => \Modules\Category\Models\Category::mostSpecificId($categories)]);
        }
        if (!empty($categories)) {
            $this->merge(['categories' => array_map('intval', $categories)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'sku'                  => 'nullable|string|max:100|unique:products,sku',
            'barcode'              => 'nullable|string|max:100|unique:products,barcode',
            'model'                => 'nullable|string|max:100',
            'category_id'          => 'required|integer|exists:categories,id',
            'categories'           => 'required|array|min:1',
            'categories.*'         => 'integer|exists:categories,id',
            'brand_id'             => 'nullable|integer|exists:brands,id',
            'supplier_id'          => 'nullable|integer',
            'unit_id'              => 'nullable|integer|exists:units,id',
            'purchase_unit_id'     => 'nullable|integer|exists:units,id',
            'sale_unit_id'         => 'nullable|integer|exists:units,id',
            'product_type'         => 'required|string|in:simple,variable,service',
            'cost_price'           => 'required|numeric|min:0',
            'sell_price'           => 'required|numeric|min:0',
            'wholesale_price'      => 'nullable|numeric|min:0',
            'resell_price'         => 'nullable|numeric|min:0',
            'vat_rate'             => 'nullable|numeric|min:0|max:100',
            'vat_inclusive'         => 'nullable|string|in:yes,no',
            'discount_type'        => 'nullable|string|in:none,percentage,fixed',
            'discount_value'       => 'nullable|numeric|min:0',
            'long_description'     => 'nullable|string|max:10000',
            'warranty'             => 'nullable|string|max:255',
            'weight'               => 'nullable|numeric|min:0',
            'country_of_origin'    => 'nullable|string|max:100',
            'min_stock_alert'      => 'nullable|integer|min:0',
            'position'             => 'nullable|integer|min:0',
            'status'               => 'nullable|string|in:active,inactive,draft',
            'show_in_pos'          => 'nullable',
            'track_stock'          => 'nullable',
            'allow_negative_stock' => 'nullable',
            'ecom_sync'            => 'nullable',
            'ecom_visible'         => 'nullable',
            'thumbnail'            => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'images'               => 'nullable|array',
            'images.*'             => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'image_order'          => 'nullable|array',
            'image_order.*'        => 'string|max:32',
            'tags'                 => 'nullable|string|max:1000',
            'seo_title'            => 'nullable|string|max:255',
            'seo_description'      => 'nullable|string|max:500',
            'seo_image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'stock'                  => 'nullable|array',
            'stock.*.quantity'       => 'nullable|integer|min:0',
            'stock.*.min_alert'      => 'nullable|integer|min:0',
            'variants'                         => 'nullable|array',
            'variants.*.sku'                   => 'nullable|string|max:100',
            'variants.*.barcode'               => 'nullable|string|max:100',
            'variants.*.cost_price'            => 'nullable|numeric|min:0',
            'variants.*.sell_price'            => 'nullable|numeric|min:0',
            'variants.*.is_active'             => 'nullable',
            'variants.*.attribute_value_ids'   => 'required_with:variants|array|min:1',
            'variants.*.attribute_value_ids.*' => 'integer|exists:variant_attribute_values,id',
            // Opening stock per variant is a single quantity from the matrix
            // (name="variants[i][stock]"). The store handler also accepts an
            // array (sums it), but the create form only ever sends a scalar.
            'variants.*.stock'                 => 'nullable|integer|min:0',
            'default_variant_index'            => 'nullable|integer|min:0',
            // Per-product Size Chart overrides — shape: { value_id: { row_id: "string" } }
            'size_chart_overrides'             => 'nullable|array',
            'size_chart_overrides.*'           => 'array',
            'size_chart_overrides.*.*'         => 'nullable|string|max:64',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'category_id'       => 'category',
            'brand_id'          => 'brand',
            'unit_id'           => 'unit',
            'purchase_unit_id'  => 'purchase unit',
            'sale_unit_id'      => 'sales unit',
            'supplier_id'       => 'supplier',
            'cost_price'        => 'cost price',
            'sell_price'        => 'sell price',
            'wholesale_price'   => 'wholesale price',
            'resell_price'      => 'reseller price',
            'vat_rate'          => 'VAT rate',
            'vat_inclusive'     => 'VAT inclusive',
            'discount_type'     => 'discount type',
            'discount_value'    => 'discount value',
            'product_type'      => 'product type',
            'long_description'  => 'long description',
            'country_of_origin' => 'country of origin',
            'min_stock_alert'   => 'minimum stock alert',
            'show_in_pos'       => 'show in POS',
            'track_stock'       => 'track stock',
            'seo_title'         => 'SEO title',
            'seo_description'   => 'SEO description',
        ];
    }
}
