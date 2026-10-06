<x-txn-shell active="history" title="Riwayat Transaksi · GPlayNation" :pending="$pending ?? null" :latest="$latest ?? null">
    <div class="mx-auto max-w-[1120px] p-4 sm:p-6 md:p-[45px]">
        <div class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end sm:justify-between sm:gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[1.54px] text-[#60A5FA]">Akun saya</p>
                <h1 class="mt-1 text-[28px] font-bold leading-[36px] text-white">Riwayat Transaksi</h1>
                <p class="mt-1 text-[14px] text-white/60">Pantau seluruh pembelian dan status pembayaranmu.</p>
            </div>
            <a href="{{ route('store.index') }}" class="flex h-[42px] items-center rounded-[14px] border border-[#28282C] px-5 text-[14px] font-medium text-white transition hover:bg-white/5">Beli Game Lain</a>
        </div>

        <div class="mt-6 grid grid-cols-1 divide-y divide-[#222224] border-y border-[#222224] sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-4 sm:p-6">
                <p class="text-[12px] text-white/60">Total transaksi</p>
                <p class="mt-1 text-[20px] font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-[12px] text-white/60">Berhasil</p>
                <p class="mt-1 text-[20px] font-bold text-white">{{ $stats['success'] }}</p>
            </div>
            <div class="p-4 sm:p-6">
                <p class="text-[12px] text-white/60">Total belanja</p>
                <p class="mt-1 text-[20px] font-bold text-white">{{ \App\Support\Money::format($stats['spent']) }}</p>
            </div>
        </div>

        @if ($transaksis->isEmpty() && ! request('status'))
            <div class="mt-6 flex flex-col items-center gap-3 rounded-2xl border border-[#222224] bg-[#161618] px-8 py-16 text-center">
                <x-icon name="wallet" class="h-10 w-10 text-white/30" />
                <h2 class="text-[16px] font-semibold text-white">Belum ada transaksi</h2>
                <p class="text-[14px] text-white/60">Pembelian yang kamu lakukan akan muncul di sini.</p>
                <a href="{{ route('store.index') }}" class="mt-2 flex h-[42px] items-center rounded-[14px] bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:brightness-110">Jelajahi store</a>
            </div>
        @else
            <section class="mt-6 overflow-hidden rounded-2xl border border-[#222224] bg-[#161618]">
                <div class="flex items-center justify-between gap-4 p-5">
                    <h2 class="text-[14px] font-semibold text-white">Semua Transaksi</h2>
                    <form method="GET" action="{{ route('transactions.index') }}">
                        <select name="status" onchange="this.form.submit()" class="h-[38px] rounded-xl border border-[#222224] bg-[#0F0F10] px-3 text-[12px] text-white/70 focus:outline-none">
                            <option value="">Semua status</option>
                            @foreach (['pending' => 'Menunggu', 'success' => 'Berhasil', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="h-[41px] border-y border-[#222224] text-[10px] font-semibold uppercase tracking-wide text-white/40">
                                <th class="px-[18px] font-medium">TRANSAKSI</th>
                                <th class="px-[18px] font-medium">PRODUK</th>
                                <th class="px-[18px] font-medium">METODE</th>
                                <th class="px-[18px] font-medium">TOTAL</th>
                                <th class="px-[18px] font-medium">STATUS</th>
                                <th class="px-[18px]"><span class="sr-only">Detail</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $methodLabels = ['e_wallet' => 'E-Wallet', 'virtual_account' => 'Virtual Account', 'credit_card' => 'Credit Card'];
                            @endphp
                            @foreach ($transaksis as $transaksi)
                                @php
                                    $methodValue = $transaksi->pembayaran?->method?->value;
                                    $itemName = $transaksi->iap?->name ?? $transaksi->game?->name ?? 'Unknown item';
                                @endphp
                                <tr class="h-[71px] border-t border-[#222224] first:border-t-0">
                                    <td class="px-[18px]">
                                        <p class="text-[13px] font-semibold text-white">{{ $transaksi->order_id }}</p>
                                        <p class="text-[11px] text-white/60">{{ $transaksi->created_at->format('d M Y, H:i') }}</p>
                                    </td>
                                    <td class="px-[18px]">
                                        <span class="flex items-center gap-2.5">
                                            @if ($transaksi->game?->cover_path)
                                                <img src="{{ asset('storage/'.$transaksi->game->cover_path) }}" alt="" aria-hidden="true" class="h-[34px] w-[44px] shrink-0 rounded object-cover">
                                            @endif
                                            <span class="text-[13px] font-semibold text-white">{{ $itemName }}</span>
                                        </span>
                                    </td>
                                    <td class="px-[18px] text-[13px] text-white/60">{{ $methodValue ? ($methodLabels[$methodValue] ?? $methodValue) : '—' }}</td>
                                    <td class="px-[18px] text-[13px] font-bold text-white">{{ \App\Support\Money::format($transaksi->total) }}</td>
                                    <td class="px-[18px]"><x-status-pill :status="$transaksi->status" /></td>
                                    <td class="px-[18px]"><a href="{{ route('transactions.show', $transaksi->order_id) }}" class="text-[12px] font-medium text-[#60A5FA] hover:underline">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="mt-6">{{ $transaksis->links() }}</div>
        @endif
    </div>
</x-txn-shell>
