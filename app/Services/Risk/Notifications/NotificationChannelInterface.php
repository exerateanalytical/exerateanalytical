<?php

namespace App\Services\Risk\Notifications;

interface NotificationChannelInterface
{
    public function send(array $payload): void;
}
