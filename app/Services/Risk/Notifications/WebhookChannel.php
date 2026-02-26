<?php

namespace App\Services\Risk\Notifications;

use Illuminate\Support\Facades\Http;

class WebhookChannel implements NotificationChannelInterface
{
    public function send(array $payload): void
    {
        $url     = config('risk_notifications.webhook.url', '');
        $timeout = (int) config('risk_notifications.webhook.timeout', 5);
        $secret  = config('risk_notifications.webhook.secret', '');

        if (empty($url)) {
            return;
        }

        $request = Http::timeout($timeout);

        if ($secret !== '') {
            $request = $request->withHeaders([
                'X-Risk-Signature' => hash_hmac('sha256', json_encode($payload), $secret),
            ]);
        }

        $request->post($url, $payload);
    }
}
