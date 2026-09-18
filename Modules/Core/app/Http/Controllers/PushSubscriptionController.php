<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Models\PushSubscription;
use Modules\Core\Services\WebPushService;

class PushSubscriptionController extends Controller
{
    public function __construct(private WebPushService $webPush)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:191'],
            'keys.auth'   => ['required', 'string', 'max:191'],
        ]);

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'p256dh'  => $data['keys']['p256dh'],
            ],
            [
                'endpoint'     => $data['endpoint'],
                'auth'         => $data['keys']['auth'],
                'user_agent'   => substr((string) $request->userAgent(), 0, 500),
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'id'      => $sub->id,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['success' => true]);
    }

    public function test(Request $request): JsonResponse
    {
        $sent = $this->webPush->sendToUser($request->user()->id, [
            'title' => 'BizPOS Pro',
            'body'  => 'Browser notifications are working — you will be alerted on new orders.',
            'tag'   => 'webpush-test',
            'url'   => url('/'),
        ]);

        return response()->json([
            'success' => $sent > 0,
            'sent'    => $sent,
        ]);
    }
}
