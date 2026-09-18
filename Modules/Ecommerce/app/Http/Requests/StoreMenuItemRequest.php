<?php

namespace Modules\Ecommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Ecommerce\Models\MenuItem;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id'  => 'nullable|integer|exists:menu_items,id',
            'type'       => 'required|string|in:' . implode(',', MenuItem::TYPES),
            'label'      => 'required|string|max:255',
            'value'      => 'nullable|string|max:500',
            'target'     => 'nullable|in:_self,_blank',
            'icon'       => 'nullable|string|max:100',
            'css_class'  => 'nullable|string|max:255',
            'visibility' => 'nullable|in:all,guest,auth',
            'settings'   => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Per-type validation of `value` — keeps the `route` type locked to the
     * whitelist and ensures categories/widgets reference real targets.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $type  = $this->input('type');
            $value = $this->input('value');

            match ($type) {
                'route' => in_array($value, MenuItem::menuRouteNames(), true)
                    ?: $v->errors()->add('value', 'Selected route is not allowed.'),
                'category' => \Modules\Category\Models\Category::whereKey($value)->exists()
                    ?: $v->errors()->add('value', 'Selected category does not exist.'),
                'page' => \Modules\Ecommerce\Models\Page::whereKey($value)->exists()
                    ?: $v->errors()->add('value', 'Selected page does not exist.'),
                'url' => filled($value)
                    ?: $v->errors()->add('value', 'A URL is required.'),
                'widget' => in_array($value, MenuItem::WIDGETS, true)
                    ?: $v->errors()->add('value', 'Unknown widget.'),
                default => null, // heading, categories_dropdown — no value needed
            };

            // Custom URLs must not carry a javascript: (or similar) scheme.
            if ($type === 'url' && filled($value) && preg_match('/^\s*(javascript|data|vbscript):/i', (string) $value)) {
                $v->errors()->add('value', 'This URL scheme is not allowed.');
            }
        });
    }
}
