<?php

namespace App\Services\Payment;

class Signature
{
    public static function gross(string $total): string
    {
        return number_format((float) $total, 2, '.', '');
    }

    public static function make(string $orderId, string $statusCode, string $grossAmount, string $key): string
    {
        return hash('sha512', $orderId . $statusCode . $grossAmount . $key);
    }

    public static function isValid(array $payload, string $key): bool
    {
        $expected = static::make(
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['status_code'] ?? ''),
            (string) ($payload['gross_amount'] ?? ''),
            $key
        );

        return hash_equals($expected, (string) ($payload['signature_key'] ?? ''));
    }
}
