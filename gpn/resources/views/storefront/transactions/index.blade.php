<x-app-layout title="Transactions">
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="screen-title">Transactions</h1>
                <p class="text-[16.2px] text-muted">Your purchase and IAP history.</p>
            </div>
            <a href="{{ route('profile.show') }}" class="btn-ghost !py-2">
                <x-icon name="user" class="h-4 w-4" /> Back to profile
            </a>
        </div>

        @if ($transaksis->isEmpty())
            <div class="card flex flex-col items-center gap-3 px-8 py-16 text-center">
                <x-icon name="wallet" class="h-10 w-10 text-faint" />
                <h2 class="section-heading">No transactions yet</h2>
                <p class="text-[16.2px] text-subtle">Purchases you make will show up here.</p>
                <a href="{{ route('store.index') }}" class="btn-primary mt-2">Browse the store</a>
            </div>
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[16.2px]">
                        <thead class="border-b border-border-strong text-sm text-muted">
                            <tr>
                                <th class="px-5 py-3 font-medium">Date</th>
                                <th class="px-5 py-3 font-medium">Order ID</th>
                                <th class="px-5 py-3 font-medium">Item</th>
                                <th class="px-5 py-3 font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksis as $transaksi)
                                @php
                                    $itemName = $transaksi->iap?->name ?? $transaksi->game?->name ?? 'Unknown item';
                                @endphp
                                <tr class="border-b border-border-strong/60 last:border-0 transition hover:bg-elevated/40">
                                    <td class="whitespace-nowrap px-5 py-3 text-subtle">{{ $transaksi->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('transactions.show', $transaksi) }}" class="font-medium text-link hover:underline">{{ $transaksi->order_id }}</a>
                                    </td>
                                    <td class="px-5 py-3">{{ $itemName }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium">{{ \App\Support\Money::format($transaksi->total) }}</td>
                                    <td class="px-5 py-3"><x-status-pill :status="$transaksi->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $transaksis->links() }}</div>
        @endif
    </div>
</x-app-layout>
