<x-app-layout :title="'Checkout · '.$trx->order_id">
    <div class="mx-auto max-w-2xl space-y-6">
        <a href="{{ route('store.show', $trx->game->slug) }}" class="inline-flex items-center gap-1 text-sm text-subtle transition hover:text-fg">
            <x-icon name="chevron-left" class="h-4 w-4" /> Back to {{ $trx->game->name }}
        </a>

        <h1 class="screen-title">Order summary</h1>

        <section class="card divide-y divide-border-strong/60">
            <div class="flex items-center gap-4 p-5">
                <div class="h-16 w-28 shrink-0 overflow-hidden rounded-lg bg-elevated">
                    <img src="{{ asset('storage/'.$trx->game->cover_path) }}" alt="{{ $trx->game->name }} cover" class="h-full w-full object-cover">
                </div>
                <div class="min-w-0">
                    <p class="font-medium">{{ $trx->iap?->name ?? $trx->game->name }}</p>
                    <p class="text-sm text-faint">{{ $trx->game->developer?->studio_name ?? 'GPlayNation' }}</p>
                </div>
            </div>

            @php $promo = $trx->iap === null ? $trx->game->activePromotion() : null; @endphp
            <div class="space-y-3 p-5">
                @if ($promo)
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted">Original price</span>
                        <span class="text-faint line-through">{{ \App\Support\Money::format($trx->game->price) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted">Promotion</span>
                        <span class="text-success">-{{ $promo->discount_percent }}%</span>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4">
                    <span class="text-muted">Total due</span>
                    <span class="text-[18.9px] font-semibold">{{ \App\Support\Money::format($trx->total) }}</span>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('checkout.pay', $trx->order_id) }}" class="card space-y-4 p-5">
            @csrf
            <label class="flex items-start gap-3 text-[16.2px] text-muted">
                <input type="checkbox" name="agreement" @checked(old('agreement')) class="mt-0.5 h-4 w-4 rounded border-border-strong bg-elevated accent-accent">
                <span>I agree to the terms of sale and understand that in-app purchases and game purchases are final.</span>
            </label>
            @error('agreement')
                <p class="field-error">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">
                    <x-icon name="wallet" class="h-5 w-5" /> Pay {{ \App\Support\Money::format($trx->total) }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('checkout.cancel', $trx->order_id) }}">
            @csrf
            <button type="submit" class="btn-ghost">Cancel order</button>
        </form>
    </div>
</x-app-layout>
