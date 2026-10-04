@props(['game'])
<a href="{{ route('store.show', $game->slug) }}" class="card group block overflow-hidden transition hover:border-accent">
    <div class="aspect-video overflow-hidden bg-elevated">
        <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} cover" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
    </div>
    <div class="space-y-1.5 p-4">
        <p class="truncate font-semibold" title="{{ $game->name }}">{{ $game->name }}</p>
        <p class="truncate text-sm text-faint">
            {{ $game->genre }}@if ($game->tags->isNotEmpty()) · {{ $game->tags->pluck('name')->join(', ') }}@endif
        </p>
        <x-price :game="$game" />
    </div>
</a>
