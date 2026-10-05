<x-app-layout :title="$game->name">
    <div class="space-y-8">
        <a href="{{ route('store.index') }}" class="inline-flex items-center gap-1 text-sm text-subtle transition hover:text-fg">
            <x-icon name="chevron-left" class="h-4 w-4" /> Back to store
        </a>

        <section class="grid gap-8 lg:grid-cols-[1.6fr_1fr]">
            <div class="space-y-3">
                <div class="card overflow-hidden">
                    <div class="aspect-video bg-elevated">
                        <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} cover" class="h-full w-full object-cover">
                    </div>
                </div>
                @if ($game->screenshots->isNotEmpty())
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach ($game->screenshots as $screenshot)
                            <img src="{{ asset('storage/'.$screenshot->path) }}" alt="{{ $game->name }} screenshot" class="h-20 w-36 shrink-0 rounded-lg border border-border-strong object-cover">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-5">
                <div class="space-y-2">
                    <h1 class="screen-title">{{ $game->name }}</h1>
                    @if ($game->developer)
                        <p class="text-[16.2px] text-muted">{{ $game->developer->studio_name }}</p>
                    @endif
                    <p class="text-sm text-faint">{{ $game->genre }}</p>
                </div>

                @if ($game->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($game->tags as $tag)
                            <span class="rounded bg-elevated px-2.5 py-1 text-sm text-subtle">#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="card space-y-4 p-5">
                    <div class="text-[18.9px]"><x-price :game="$game" /></div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($inLibrary || $game->isFree())
                            <a href="{{ route('games.download', $game->slug) }}" class="btn-primary"><x-icon name="download" class="h-5 w-5" /> Download</a>
                        @else
                            <form method="POST" action="{{ route('checkout.store', ['game', $game->id]) }}">@csrf<button class="btn-primary">Buy now</button></form>
                        @endif
                        @auth
                            <form method="POST" action="{{ route('wishlist.toggle', $game) }}">@csrf
                                <button class="btn-ghost"><x-icon name="heart" class="h-5 w-5 {{ $wishlisted ? 'text-danger' : '' }}" /> {{ $wishlisted ? 'Wishlisted' : 'Wishlist' }}</button>
                            </form>
                        @endauth
                    </div>
                </div>

                @if ($game->trailer_url)
                    <a href="{{ $game->trailer_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-[16.2px] text-link hover:underline">
                        Watch trailer
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    </a>
                @endif
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="section-heading">About this game</h2>
            <p class="whitespace-pre-line text-[16.2px] text-muted">{{ $game->description }}</p>
        </section>

        @if ($iaps->isNotEmpty())
            <section class="space-y-4">
                <h2 class="section-heading">In-app purchases</h2>
                <div class="space-y-3">
                    @foreach ($iaps as $iap)
                        <div class="card flex items-center justify-between gap-4 p-4">
                            <div class="min-w-0">
                                <p class="font-medium">{{ $iap->name }}</p>
                                <p class="text-sm text-faint">{{ ucfirst($iap->type->value) }}@if ($iap->description) · {{ $iap->description }}@endif</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold">{{ \App\Support\Money::format($iap->price) }}</span>
                                <form method="POST" action="{{ route('checkout.store', ['iap', $iap->id]) }}">@csrf<button class="btn-ghost">Buy</button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
