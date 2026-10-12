<?php

namespace App\Console\Commands;

use App\Enums\TransaksiStatus;
use App\Models\Transaksi;
use Illuminate\Console\Command;

class ExpireTransactions extends Command
{
    protected $signature = 'transactions:expire';

    protected $description = 'Mark stale pending transactions as expired';

    public function handle(): int
    {
        $count = Transaksi::where('status', TransaksiStatus::Pending->value)
            ->where('created_at', '<', now()->subHours(config('gpn.transaction_ttl_hours')))
            ->update(['status' => TransaksiStatus::Expired->value]);

        $this->info("Expired {$count} stale pending transaction(s).");

        return self::SUCCESS;
    }
}
