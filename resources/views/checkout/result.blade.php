<x-txn-shell active="status" :title="'Status Pembayaran · GPlayNation'" :trx="$trx" :pending="$trx->status === \App\Enums\TransaksiStatus::Pending ? $trx : null">
    @php
        $status = $trx->status;
        $game = $trx->game;
        $isGamePurchase = $trx->iap_id === null;
        $final = $status !== \App\Enums\TransaksiStatus::Pending;
        $methodLabels = ['e_wallet' => 'E-Wallet', 'virtual_account' => 'Virtual Account', 'credit_card' => 'Credit Card'];
        $methodValue = $trx->pembayaran?->method?->value;
        $copy = match ($status) {
            \App\Enums\TransaksiStatus::Success => ['ok', 'PEMBAYARAN BERHASIL', 'Game siap dimainkan', 'Pembayaranmu sudah terverifikasi. '.($trx->iap?->name ?? $game?->name ?? 'Item').' kini tersedia di library akunmu.'],
            \App\Enums\TransaksiStatus::Failed => ['danger', 'PEMBAYARAN GAGAL', 'Pembayaran gagal', 'Tidak ada dana yang berpindah. Kamu bisa mencoba lagi kapan saja.'],
            \App\Enums\TransaksiStatus::Expired => ['danger', 'PEMBAYARAN KEDALUWARSA', 'Pesanan kedaluwarsa', 'Pesanan ini telah kedaluwarsa. Buat checkout baru untuk melanjutkan.'],
            default => ['warn', 'MENUNGGU PEMBAYARAN', 'Menunggu pembayaran', 'Kami belum menerima notifikasi penyelesaian untuk pesanan ini.'],
        };
        [$tone, $eyebrow, $heading, $sub] = $copy;
        $ring = $tone === 'ok' ? 'border-[#30D158]/20' : ($tone === 'warn' ? 'border-amber-400/25' : 'border-[#FF453A]/25');
        $glow = $tone === 'ok' ? 'bg-[#30D158]/15' : ($tone === 'warn' ? 'bg-amber-400/10' : 'bg-[#FF453A]/10');
        $ink = $tone === 'ok' ? 'text-[#30D158]' : ($tone === 'warn' ? 'text-amber-400' : 'text-[#FF453A]');
        $chipBg = $tone === 'ok' ? 'bg-[#30D158]/10' : ($tone === 'warn' ? 'bg-amber-400/10' : 'bg-[#FF453A]/10');
    @endphp
    <div class="mx-auto max-w-[1120px] p-4 sm:p-6 md:p-[45px]">
        <div class="flex justify-center">
            <x-checkout-steps :current="$final ? 3 : 2" :done-all="$status === \App\Enums\TransaksiStatus::Success" />
        </div>

        <section class="relative mx-auto mt-8 w-[600px] max-w-full overflow-hidden rounded-[20px] border {{ $ring }} bg-[#161618] p-5 text-center sm:p-[42px]">
            <div class="pointer-events-none absolute -top-24 left-1/2 h-48 w-[420px] -translate-x-1/2 rounded-full {{ $glow }} blur-[80px]" aria-hidden="true"></div>
            <div class="relative">
                <span class="mx-auto grid h-[58px] w-[58px] place-items-center rounded-full border {{ $ring }} {{ $chipBg }} {{ $ink }} text-[25px] font-bold">
                    @if ($status === \App\Enums\TransaksiStatus::Success)
                        ✓
                    @elseif ($status === \App\Enums\TransaksiStatus::Pending)
                        …
                    @else
                        <x-icon name="x" class="h-6 w-6" />
                    @endif
                </span>
                <p class="mt-5 text-[11px] font-semibold uppercase tracking-[1.54px] {{ $ink }}">{{ $eyebrow }}</p>
                <h1 class="mt-2 text-[28px] font-bold leading-[36px] text-white">{{ $heading }}</h1>
                <p class="mx-auto mt-2 max-w-[420px] text-[14px] leading-[20px] text-white/60">{{ $sub }}</p>

                <dl class="mt-6 rounded-[14px] border border-[#222224] bg-black/40 px-[22px] py-2 text-left">
                    <div class="flex h-[45px] items-center justify-between gap-4"><dt class="text-[12px] text-white/60">ID Transaksi</dt><dd class="text-[13px] font-semibold text-white">{{ $trx->order_id }}</dd></div>
                    <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Waktu</dt><dd class="text-[13px] font-semibold text-white">{{ ($trx->pembayaran?->paid_at ?? $trx->created_at)->format('d M Y, H:i') }}</dd></div>
                    <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Metode</dt><dd class="text-[13px] font-semibold text-white">{{ $methodValue ? ($methodLabels[$methodValue] ?? $methodValue) : '—' }}</dd></div>
                    <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Total</dt><dd class="text-[13px] font-semibold text-white">{{ \App\Support\Money::format($trx->total) }}</dd></div>
                </dl>

                <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @if ($status === \App\Enums\TransaksiStatus::Success && $isGamePurchase && $game)
                        <a href="{{ route('games.download', $game->slug) }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Lihat Game</a>
                    @elseif ($status === \App\Enums\TransaksiStatus::Pending)
                        <a href="{{ route('gateway.show', $trx->order_id) }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Bayar Sekarang</a>
                    @else
                        <a href="{{ $game ? route('store.show', $game->slug) : route('store.index') }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Coba Lagi</a>
                    @endif
                    <a href="{{ route('transactions.index') }}" class="flex h-[42px] items-center justify-center rounded-[14px] border border-[#28282C] px-5 text-[14px] font-medium text-white transition hover:bg-white/5">Riwayat Transaksi</a>
                </div>
            </div>
        </section>
    </div>
</x-txn-shell>
