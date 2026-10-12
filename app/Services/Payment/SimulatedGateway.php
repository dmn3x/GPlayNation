<?php

namespace App\Services\Payment;

use App\Models\Transaksi;

class SimulatedGateway
{
    /** Build a Midtrans-shaped, properly signed notification (SRS activity 3-2.2/3-2.4). */
    public static function payload(Transaksi $trx, string $transactionStatus, string $method): array
    {
        $statusCode = $transactionStatus === 'deny' ? '202' : '200';
        $gross = Signature::gross($trx->total);

        return [
            'order_id' => $trx->order_id,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus, // settlement|pending|deny|expire
            'gross_amount' => $gross,
            'payment_type' => $method,                  // e_wallet|virtual_account|credit_card
            'transaction_time' => now()->toIso8601String(),
            'signature_key' => Signature::make($trx->order_id, $statusCode, $gross, (string) config('services.gpn_payment.server_key')),
        ];
    }
}
