<?php

namespace Modules\Marketing\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Marketing\Contracts\SmsGatewayInterface;
use Modules\Setting\Models\Setting;

class BulkSmsBdGateway implements SmsGatewayInterface
{
    protected string $apiKey;
    protected string $senderId;
    protected string $apiUrl = 'https://bulksmsbd.net/api/smsapi';
    protected string $bulkApiUrl = 'https://bulksmsbd.net/api/smsapimany';

    public function __construct()
    {
        $smsSettings = Setting::getGroup('sms');
        $this->apiKey = $smsSettings['api_key'] ?? '';
        $this->senderId = $smsSettings['sender_id'] ?? '';
    }

    public function send(string $number, string $message): bool
    {
        try {
            $response = Http::timeout(30)->post($this->apiUrl, [
                'api_key'    => $this->apiKey,
                'senderid'   => $this->senderId,
                'number'     => $number,
                'message'    => $message,
                'type'       => 'text',
            ]);

            return $response->successful() && ($response->json('response_code') == 202);
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD SMS failed', ['number' => $number, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function sendBulk(array $numbers, string $message): array
    {
        $numbers = array_values(array_filter($numbers));
        $total = count($numbers);

        if ($total === 0) {
            return ['sent' => 0, 'failed' => 0];
        }

        // One-to-many: same message to many numbers in a single request.
        try {
            $response = Http::timeout(60)->post($this->bulkApiUrl, [
                'api_key'  => $this->apiKey,
                'senderid' => $this->senderId,
                'number'   => implode(',', $numbers),
                'message'  => $message,
            ]);

            $ok = $response->successful() && ($response->json('response_code') == 202);

            return $ok
                ? ['sent' => $total, 'failed' => 0]
                : ['sent' => 0, 'failed' => $total];
        } catch (\Throwable $e) {
            Log::error('BulkSMSBD bulk SMS failed', ['count' => $total, 'error' => $e->getMessage()]);

            return ['sent' => 0, 'failed' => $total];
        }
    }
}
