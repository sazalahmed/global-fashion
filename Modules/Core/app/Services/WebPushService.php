<?php

namespace Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Modules\Core\Models\PushSubscription;

class WebPushService
{
    private ?WebPush $webPush = null;

    private function client(): WebPush
    {
        if ($this->webPush) {
            return $this->webPush;
        }

        $subject = config('webpush.vapid.subject');
        $public  = config('webpush.vapid.public_key');
        $private = config('webpush.vapid.private_key');

        if (empty($public) || empty($private)) {
            throw new \RuntimeException('VAPID keys not configured. Run: php artisan webpush:vapid');
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => $subject,
                'publicKey'  => $public,
                'privateKey' => $private,
            ],
        ]);

        $this->webPush->setDefaultOptions([
            'TTL' => (int) config('webpush.defaults.ttl', 86400),
        ]);

        return $this->webPush;
    }

    /**
     * Send a push to every active subscription for one user.
     */
    public function sendToUser(int $userId, array $payload): int
    {
        $subs = PushSubscription::where('user_id', $userId)->get();

        return $this->dispatch($subs, $payload);
    }

    /**
     * Send a push to every user that has the given Spatie role.
     */
    public function sendToRole(string $role, array $payload): int
    {
        $userIds = User::role($role)->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        $subs = PushSubscription::whereIn('user_id', $userIds)->get();

        return $this->dispatch($subs, $payload);
    }

    /**
     * Send a push to every subscription in the system.
     */
    public function broadcast(array $payload): int
    {
        $subs = PushSubscription::all();

        return $this->dispatch($subs, $payload);
    }

    /**
     * Push the payload to a collection of subscriptions and clean up dead endpoints.
     * Returns the number of pushes successfully queued with the push service.
     */
    private function dispatch($subs, array $payload): int
    {
        if ($subs->isEmpty()) {
            return 0;
        }

        $body = $this->normalizePayload($payload);
        $webPush = $this->client();

        foreach ($subs as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'publicKey'       => $sub->p256dh,
                'authToken'       => $sub->auth,
                'contentEncoding' => 'aesgcm',
            ]);

            $webPush->queueNotification($subscription, json_encode($body));
        }

        $sent = 0;
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            $sub      = $subs->firstWhere('endpoint', $endpoint);

            if ($report->isSuccess()) {
                $sent++;
                if ($sub) {
                    $sub->forceFill(['last_used_at' => now()])->save();
                }
                continue;
            }

            if ($report->isSubscriptionExpired() && $sub) {
                $sub->delete();
                continue;
            }

            Log::warning('WebPush delivery failed', [
                'endpoint' => $endpoint,
                'reason'   => $report->getReason(),
            ]);
        }

        return $sent;
    }

    /**
     * Apply defaults (icon, badge) to outgoing payload and enforce expected shape.
     */
    private function normalizePayload(array $payload): array
    {
        return array_merge([
            'title' => 'BizPOS Pro',
            'body'  => '',
            'icon'  => config('webpush.defaults.icon'),
            'badge' => config('webpush.defaults.badge'),
            'tag'   => 'bizpos-notification',
            'url'   => '/',
        ], $payload);
    }
}
