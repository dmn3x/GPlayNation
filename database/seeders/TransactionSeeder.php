<?php

namespace Database\Seeders;

use App\Enums\LibrarySource;
use App\Enums\PaymentMethod;
use App\Enums\TransaksiStatus;
use App\Models\Download;
use App\Models\Game;
use App\Models\Komisi;
use App\Models\KomisiTransaksi;
use App\Models\Library;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\CommissionService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gplaynation.test')->firstOrFail();

        $komisi = Komisi::updateOrCreate(
            ['effective_from' => '2026-01-01'],
            ['admin_id' => $admin->id, 'percentage' => '20.00'],
        );

        $service = new CommissionService;
        $buyers = User::whereIn('email', ['user@gplaynation.test', 'user2@gplaynation.test', 'user3@gplaynation.test'])->get();
        $paidGames = Game::published()->where('price', '>', 0)->get();
        $methods = PaymentMethod::cases();

        // 40–70 settled transactions spread over the last 90 days.
        $count = 55;
        for ($i = 0; $i < $count; $i++) {
            $game = $paidGames[$i % $paidGames->count()];
            $buyer = $buyers[$i % $buyers->count()];
            $at = now()->subDays($i % 90)->subHours($i % 24)->subMinutes($i % 60);

            $trx = Transaksi::create([
                'order_id' => $this->uniqueOrderId($at),
                'user_id' => $buyer->id,
                'game_id' => $game->id,
                'iap_id' => null,
                'type' => 'game',
                'total' => $game->price,
                'status' => TransaksiStatus::Success,
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            Pembayaran::create([
                'transaksi_id' => $trx->id,
                'method' => $methods[$i % count($methods)],
                'amount' => $trx->total,
                'reference_no' => 'REF-'.strtoupper(Str::random(10))."-{$i}",
                'payload' => ['seeded' => true],
                'paid_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            // Period-honest komisi snapshot: rate in effect at the trx's own date.
            $percentage = $service->percentageFor($trx->created_at);
            $amount = bcmul($trx->total, bcdiv($percentage, '100', 4), 2);
            KomisiTransaksi::create([
                'transaksi_id' => $trx->id,
                'komisi_id' => $komisi->id,
                'percentage' => $percentage,
                'amount' => $amount,
                'developer_amount' => bcsub($trx->total, $amount, 2),
            ]);

            Library::firstOrCreate(
                ['user_id' => $buyer->id, 'game_id' => $game->id],
                ['transaksi_id' => $trx->id, 'source' => LibrarySource::Purchase, 'is_showcased' => ($i % 3) === 0],
            );
        }

        // A few non-settled rows for realism.
        foreach ([TransaksiStatus::Pending, TransaksiStatus::Failed, TransaksiStatus::Expired] as $s => $status) {
            for ($k = 0; $k < 2; $k++) {
                $game = $paidGames[($s + $k) % $paidGames->count()];
                $buyer = $buyers[($s + $k) % $buyers->count()];
                $at = now()->subDays($k + 1);

                $trx = Transaksi::create([
                    'order_id' => $this->uniqueOrderId($at),
                    'user_id' => $buyer->id,
                    'game_id' => $game->id,
                    'iap_id' => null,
                    'type' => 'game',
                    'total' => $game->price,
                    'status' => $status,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);

                if ($status === TransaksiStatus::Pending) {
                    Pembayaran::create([
                        'transaksi_id' => $trx->id,
                        'method' => $methods[($s + $k) % count($methods)],
                        'amount' => $trx->total,
                        'reference_no' => 'REF-'.strtoupper(Str::random(10))."-{$status->value}-{$k}",
                        'payload' => ['seeded' => true],
                        'paid_at' => null,
                    ]);
                }
            }
        }

        // A handful of downloads for realism.
        $games = Game::published()->get();
        for ($i = 0; $i < 20; $i++) {
            Download::create([
                'user_id' => $buyers[$i % $buyers->count()]->id,
                'game_id' => $games[$i % $games->count()]->id,
            ]);
        }
    }

    private function uniqueOrderId(CarbonInterface $at): string
    {
        do {
            $id = 'GPN-'.$at->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Transaksi::where('order_id', $id)->exists());

        return $id;
    }
}
