<x-app-layout :title="'Payment result · '.$trx->order_id">
    @php
        $status = $trx->status;
        $game = $trx->game;
        $isGamePurchase = $trx->iap_id === null;
    @endphp

    <div class="mx-auto max-w-xl space-y-6 py-8 text-center">
        @if ($status === \App\Enums\TransaksiStatus::Success)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-success/15 text-success">
                <x-icon name="check" class="h-8 w-8" />
            </div>
            <div class="space-y-2">
                <h1 class="screen-title">Payment successful</h1>
                <p class="text-[16.2px] text-muted">{{ $trx->iap?->name ?? $game?->name }} is ready.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if ($isGamePurchase && $game)
                    <a href="{{ route('games.download', $game->slug) }}" class="btn-primary">
                        <x-icon name="download" class="h-5 w-5" /> Download game
                    </a>
                @endif
                <a href="{{ route('transactions.show', $trx->order_id) }}" class="btn-ghost">View transaction</a>
            </div>
        @elseif ($status === \App\Enums\TransaksiStatus::Failed)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-danger/15 text-danger">
                <x-icon name="x" class="h-8 w-8" />
            </div>
            <div class="space-y-2">
                <h1 class="screen-title">Payment failed — try again</h1>
                <p class="text-[16.2px] text-muted">No money moved. You can retry checkout anytime.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if ($game)
                    <a href="{{ route('store.show', $game->slug) }}" class="btn-primary">Back to {{ $game->name }}</a>
                @endif
                <a href="{{ route('transactions.show', $trx->order_id) }}" class="btn-ghost">View transaction</a>
            </div>
        @elseif ($status === \App\Enums\TransaksiStatus::Expired)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-danger/15 text-danger">
                <x-icon name="alert" class="h-8 w-8" />
            </div>
            <div class="space-y-2">
                <h1 class="screen-title">Payment expired</h1>
                <p class="text-[16.2px] text-muted">This order timed out. Start a fresh checkout to continue.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                @if ($game)
                    <a href="{{ route('store.show', $game->slug) }}" class="btn-primary">Back to {{ $game->name }}</a>
                @endif
                <a href="{{ route('transactions.show', $trx->order_id) }}" class="btn-ghost">View transaction</a>
            </div>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-400/15 text-amber-400">
                <x-icon name="alert" class="h-8 w-8" />
            </div>
            <div class="space-y-2">
                <h1 class="screen-title">Awaiting payment</h1>
                <p class="text-[16.2px] text-muted">We have not received a settlement notification for this order yet.</p>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('gateway.show', $trx->order_id) }}" class="btn-primary">Open payment gateway</a>
                <a href="{{ route('transactions.show', $trx->order_id) }}" class="btn-ghost">View transaction</a>
            </div>
        @endif
    </div>
</x-app-layout>
