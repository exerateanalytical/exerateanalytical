<?php

namespace App\Services\Risk\Notifications;

use Illuminate\Support\Facades\Http;

class WebhookChannel implements NotificationChannelInterface
{
    public function __construct(private readonly array $channelConfig = []) {}

    public function send(array $payload): void
    {
        $url     = $this->channelConfig['url'] ?? '';
        $timeout = (int) ($this->channelConfig['timeout'] ?? 5);
        $secret  = $this->channelConfig['secret'] ?? '';

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
