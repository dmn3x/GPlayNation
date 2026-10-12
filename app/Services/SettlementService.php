<?php

namespace App\Services;

use App\Enums\LibrarySource;
use App\Enums\TransaksiStatus;
use App\Exceptions\Payment\AmountMismatchException;
use App\Exceptions\Payment\InvalidSignatureException;
use App\Exceptions\Payment\PaymentNotConfiguredException;
use App\Models\IapOwnership;
use App\Models\Library;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Services\Payment\Signature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class SettlementService
{
    public function __construct(
        private CommissionService $commission,
        private LoggerInterface $log,
    ) {}

    public function handleNotification(array $payload): Transaksi
    {
        $key = config('services.gpn_payment.server_key');
        if (! $key) {
            throw new PaymentNotConfiguredException('Payment server key is not configured.');
        }
        if (! Signature::isValid($payload, $key)) {
            $this->log->warning('Payment webhook rejected: invalid signature', ['order_id' => $payload['order_id'] ?? null]);
            throw new InvalidSignatureException;
        }

        /** @var Transaksi $trx */
        $trx = Transaksi::where('order_id', $payload['order_id'])->firstOrFail();

        if (Signature::gross($trx->total) !== (string) $payload['gross_amount']) {
            throw new AmountMismatchException;
        }

        if ($trx->isFinal()) {
            return $trx; // idempotent replay
        }

        return match ($payload['transaction_status']) {
            'settlement', 'capture' => $this->settle($trx, $payload),
            'pending' => $trx,
            'expire' => $this->mark($trx, TransaksiStatus::Expired),
            default => $this->mark($trx, TransaksiStatus::Failed), // deny|cancel
        };
    }

    private function settle(Transaksi $trx, array $payload): Transaksi
    {
        return DB::transaction(function () use ($trx, $payload) {
            $trx = Transaksi::whereKey($trx->getKey())->lockForUpdate()->first();
            if ($trx->isFinal()) {
                return $trx;
            }

            $trx->update(['status' => TransaksiStatus::Success]);
            Pembayaran::create([
                'transaksi_id' => $trx->id,
                'method' => $payload['payment_type'],
                'amount' => $trx->total,
                'reference_no' => 'REF-'.strtoupper(Str::random(10)),
                'payload' => $payload,
                'paid_at' => now(),
            ]);

            if ($trx->type === 'iap' && $trx->iap_id) {
                IapOwnership::firstOrCreate(
                    ['user_id' => $trx->user_id, 'iap_id' => $trx->iap_id],
                    ['transaksi_id' => $trx->id]
                );
            } else {
                Library::firstOrCreate(
                    ['user_id' => $trx->user_id, 'game_id' => $trx->game_id],
                    ['transaksi_id' => $trx->id, 'source' => LibrarySource::Purchase]
                );
            }

            $this->commission->snapshot($trx);

            return $trx->refresh();
        });
    }

    private function mark(Transaksi $trx, TransaksiStatus $status): Transaksi
    {
        Transaksi::whereKey($trx->getKey())
            ->where('status', TransaksiStatus::Pending->value)
            ->update(['status' => $status->value]);

        return $trx->refresh();
    }
}
