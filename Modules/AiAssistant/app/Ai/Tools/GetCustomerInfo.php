<?php

namespace Modules\AiAssistant\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\AiAssistant\Ai\Tools\Concerns\LogsToolInvocation;
use Stringable;

class GetCustomerInfo implements Tool
{
    use LogsToolInvocation;
    public function description(): Stringable|string
    {
        return 'Get the logged-in customer\'s name/phone/district/address for prefill. Empty for guests.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->auditInvocation($request, 'get_customer_info');

        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return json_encode([
                'logged_in' => false,
                'message' => 'No customer is logged in. Ask the user for name, phone, district, and address.',
            ]);
        }

        return json_encode([
            'logged_in' => true,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'district' => $customer->district,
            'address' => $customer->shipping_address ?: $customer->address,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
