<?php

namespace Modules\Marketing\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send SMS to a single number.
     */
    public function send(string $number, string $message): bool;

    /**
     * Send SMS to multiple numbers.
     * Returns ['sent' => int, 'failed' => int].
     */
    public function sendBulk(array $numbers, string $message): array;
}
