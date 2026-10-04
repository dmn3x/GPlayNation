@php
    $methodLabels = [
        'e_wallet' => 'E-Wallet',
        'virtual_account' => 'Virtual Account',
        'credit_card' => 'Credit Card',
    ];
    $pembayaran = $transaksi->pembayaran;
    $methodLabel = $pembayaran ? ($methodLabels[$pembayaran->method->value] ?? ucfirst($pembayaran->method->value)) : '—';
    $itemName = $transaksi->iap?->name ?? $transaksi->game?->name ?? 'Unknown item';
    $status = $transaksi->status;
    $isSettledGame = $status === \App\Enums\TransaksiStatus::Success && $transaksi->game !== null && $transaksi->iap_id === null;

    $timeline = [
        ['label' => 'Created', 'at' => $transaksi->created_at, 'done' => true],
    ];
    if ($status === \App\Enums\TransaksiStatus::Success) {
        $timeline[] = ['label' => 'Paid', 'at' => $pembayaran?->paid_at, 'done' => true];
    } elseif ($status === \App\Enums\TransaksiStatus::Failed) {
        $timeline[] = ['label' => 'Failed', 'at' => null, 'done' => true];
    } elseif ($status === \App\Enums\TransaksiStatus::Expired) {
        $timeline[] = ['label' => 'Expired', 'at' => null, 'done' => true];
    } else {
        $timeline[] = ['label' => 'Awaiting payment', 'at' => null, 'done' => false];
    }
@endphp

<x-app-layout title="Transaction {{ $transaksi->order_id }}">
    <div class="space-y-6">
        <a href="{{ route('transactions.index') }}" class="inline-flex items-center gap-1 text-sm text-subtle transition hover:text-fg">
            <x-icon name="chevron-left" class="h-4 w-4" /> Back to transactions
        </a>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="screen-title">Order {{ $transaksi->order_id }}</h1>
                <p class="text-[16.2px] text-muted">{{ $itemName }}</p>
            </div>
            <x-status-pill :status="$status" class="!px-3 !py-1 !text-[16.2px]" />
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
            <section class="card divide-y divide-border-strong/60">
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Order ID</span>
                    <span class="font-medium">{{ $transaksi->order_id }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Item</span>
                    <span class="font-medium">{{ $itemName }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Total</span>
                    <span class="text-[18.9px] font-semibold">{{ \App\Support\Money::format($transaksi->total) }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Payment method</span>
                    <span class="font-medium">{{ $methodLabel }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Reference no</span>
                    <span class="font-medium">{{ $pembayaran?->reference_no ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-4 p-5">
                    <span class="text-muted">Paid at</span>
                    <span class="font-medium">{{ $pembayaran?->paid_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="card space-y-4 p-5">
                    <h2 class="section-heading">Timeline</h2>
                    <ol class="space-y-4">
                        @foreach ($timeline as $step)
                            <li class="flex gap-3">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $step['done'] ? 'bg-success/15 text-success' : 'bg-elevated text-faint' }}">
                                    <x-icon :name="$step['done'] ? 'check' : 'alert'" class="h-3.5 w-3.5" />
                                </span>
                                <span>
                                    <span class="block font-medium {{ $step['done'] ? '' : 'text-faint' }}">{{ $step['label'] }}</span>
                                    @if ($step['at'])
                                        <span class="block text-sm text-faint">{{ $step['at']->format('d M Y, H:i') }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </section>

                @if ($isSettledGame)
                    <a href="{{ route('games.download', $transaksi->game->slug) }}" class="btn-primary w-full">
                        <x-icon name="download" class="h-5 w-5" /> Download game
                    </a>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
