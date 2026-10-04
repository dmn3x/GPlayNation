@props(['game'])
<article class="gpn-card overflow-hidden transition hover:border-gpn-border-strong">
    <a href="{{ route('store.show', $game) }}" class="block">
        <div class="relative aspect-[16/9] w-full overflow-hidden bg-gpn-elevated">
            @if ($game->cover)
                <img src="{{ $game->cover }}" alt="{{ $game->game_nama }}" loading="lazy"
                     class="h-full w-full object-cover">
            @else
                <div class="grid h-full w-full place-items-center text-gpn-fg-ghost">
                    <x-icon name="monitor" class="h-10 w-10" />
                </div>
            @endif
            <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded bg-black/70 px-2 py-1 text-[11px] font-medium uppercase tracking-wide">
                <span class="h-2 w-2 rounded-full bg-red-600"></span> Proudly Indonesian
            </span>
        </div>
    </a>

    <div class="p-4">
        <h3 class="font-semibold">
            <a href="{{ route('store.show', $game) }}" class="hover:text-gpn-link">{{ $game->game_nama }}</a>
        </h3>
        <div class="mt-2 flex flex-wrap gap-1.5">
            <span class="gpn-tag">{{ $game->kategori }}</span>
            @if ($game->game_genre !== $game->kategori)
                <span class="gpn-tag">{{ $game->game_genre }}</span>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between border-t border-gpn-border px-4 py-3">
        <span class="font-semibold">{{ $game->game_harga > 0 ? 'Rp '.number_format($game->game_harga, 0, ',', '.') : 'Free' }}</span>
        @auth
            <a href="{{ route('store.show', $game) }}" class="gpn-btn gpn-btn-primary gpn-btn-sm">Buy</a>
        @else
            <a href="{{ route('login') }}" class="gpn-btn gpn-btn-primary gpn-btn-sm">Buy</a>
        @endauth
    </div>
</article>
