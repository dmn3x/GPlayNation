<x-txn-shell active="status" :title="'Detail Transaksi · GPlayNation'" :trx="$transaksi" :pending="$pending ?? null">
    @php
        $pembayaran = $transaksi->pembayaran;
        $methodLabels = ['e_wallet' => 'E-Wallet', 'virtual_account' => 'Virtual Account', 'credit_card' => 'Credit Card'];
        $methodValue = $pembayaran?->method?->value;
        $itemName = $transaksi->iap?->name ?? $transaksi->game?->name ?? 'Unknown item';
        $isSettledGame = $transaksi->status === \App\Enums\TransaksiStatus::Success && $transaksi->game !== null && $transaksi->iap_id === null;
    @endphp
    <div class="mx-auto max-w-[1120px] p-4 sm:p-6 md:p-[45px]">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[1.54px] text-[#60A5FA]">AKUN SAYA</p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-[28px] font-bold leading-[36px] text-white">Detail Transaksi</h1>
                    <p class="mt-1 text-[14px] text-white/60">{{ $transaksi->order_id }} · {{ $itemName }}</p>
                </div>
                <x-status-pill :status="$transaksi->status" />
            </div>
        </div>

        <section class="mx-auto mt-6 w-[600px] max-w-full rounded-[20px] border border-[#222224] bg-[#161618] p-5 sm:p-[42px]">
            <div class="flex items-center gap-4">
                @if ($transaksi->game?->cover_path)
                    <img src="{{ asset('storage/'.$transaksi->game->cover_path) }}" alt="" aria-hidden="true" class="h-[80px] w-[112px] shrink-0 rounded-xl object-cover">
                @endif
                <div class="min-w-0">
                    <p class="truncate text-[16px] font-semibold text-white">{{ $itemName }}</p>
                    <p class="mt-0.5 text-[12px] text-white/60">{{ $transaksi->game?->developer?->studio_name ?? 'GPlayNation' }}</p>
                    <p class="mt-1 text-[14px] font-bold text-white">{{ \App\Support\Money::format($transaksi->total) }}</p>
                </div>
            </div>

            <dl class="mt-6 rounded-[14px] border border-[#222224] bg-black/40 px-[22px] py-2">
                <div class="flex h-[45px] items-center justify-between gap-4"><dt class="text-[12px] text-white/60">ID Transaksi</dt><dd class="text-[13px] font-semibold text-white">{{ $transaksi->order_id }}</dd></div>
                <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Waktu</dt><dd class="text-[13px] font-semibold text-white">{{ $transaksi->created_at->format('d M Y, H:i') }}</dd></div>
                <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Metode</dt><dd class="text-[13px] font-semibold text-white">{{ $methodValue ? ($methodLabels[$methodValue] ?? $methodValue) : '—' }}</dd></div>
                <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Referensi</dt><dd class="text-[13px] font-semibold text-white">{{ $pembayaran?->reference_no ?? '—' }}</dd></div>
                <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Dibayar pada</dt><dd class="text-[13px] font-semibold text-white">{{ $pembayaran?->paid_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
                <div class="flex h-[45px] items-center justify-between gap-4 border-t border-[#222224]"><dt class="text-[12px] text-white/60">Total</dt><dd class="text-[13px] font-semibold text-white">{{ \App\Support\Money::format($transaksi->total) }}</dd></div>
            </dl>

            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @if ($isSettledGame)
                    <a href="{{ route('games.download', $transaksi->game->slug) }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Lihat Game</a>
                @elseif ($transaksi->status === \App\Enums\TransaksiStatus::Pending)
                    <a href="{{ route('gateway.show', $transaksi->order_id) }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Bayar Sekarang</a>
                @else
                    <a href="{{ $transaksi->game ? route('store.show', $transaksi->game->slug) : route('store.index') }}" class="flex h-[42px] items-center justify-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Lihat Game</a>
                @endif
                <a href="{{ route('transactions.index') }}" class="flex h-[42px] items-center justify-center rounded-[14px] border border-[#28282C] px-5 text-[14px] font-medium text-white transition hover:bg-white/5">Riwayat Transaksi</a>
            </div>
        </section>
    </div>
</x-txn-shell>
