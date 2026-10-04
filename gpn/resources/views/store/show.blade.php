@extends('layouts.app')
@section('title', $game->game_nama.' — GPlayNation')

@section('content')
{{-- Figma: Game frame 6:1559 — sidebar + detail + purchase panel --}}
<div class="flex">
    <aside class="gpn-sidebar w-[220px] flex-none p-6" aria-label="Browse">
        <x-logo class="h-6 w-auto mb-6" />
        @php
            $menu = [
                ['route' => 'home', 'icon' => 'home', 'label' => 'Home'],
                ['route' => 'store.index', 'icon' => 'grid', 'label' => 'Categories'],
                ['route' => 'store.index', 'icon' => 'tag', 'label' => 'Price'],
                ['route' => 'store.index', 'icon' => 'bolt', 'label' => 'New Releases'],
                ['route' => 'store.index', 'icon' => 'clock', 'label' => 'Recent All'],
            ];
        @endphp
        <nav class="space-y-1">
            @foreach ($menu as $i => $item)
                <a href="{{ route($item['route']) }}" class="gpn-side-link {{ request()->routeIs('store.show') && $i === 0 ? 'is-active' : '' }}">
                    <x-icon :name="$item['icon']" class="h-5 w-5" /> {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="min-w-0 flex-1">
        <div class="relative h-[340px] overflow-hidden">
            <img src="{{ $game->cover }}" alt="" class="h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-gpn-bg via-transparent to-transparent"></div>
            <div class="absolute bottom-8 left-10">
                <h1 class="text-5xl font-semibold text-white">{{ $game->game_nama }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-gpn-fg-muted">
                    <span class="inline-flex items-center gap-2"><x-icon name="monitor" class="h-4 w-4" /> {{ $game->platform ?? 'Windows, Mac' }}</span>
                    <span>{{ $game->kategori ?? $game->game_genre }}</span>
                    <span>{{ $game->rilis?->format('M j, Y') }}</span>
                </div>
            </div>
        </div>

        <div class="flex gap-10 px-10 py-10">
            <div class="min-w-0 flex-1">
                <section>
                    <h2 class="text-xl font-semibold">Description</h2>
                    <hr class="my-4 border-gpn-border">
                    <p class="max-w-[75ch] leading-relaxed text-gpn-fg-muted">{{ $game->deskripsi }}</p>
                </section>

                @if ($game->screenshots)
                    <section class="mt-10">
                        <h2 class="text-xl font-semibold">Media Carousel</h2>
                        <hr class="my-4 border-gpn-border">
                        <div class="flex gap-4 overflow-x-auto pb-2">
                            @foreach ($game->screenshots as $shot)
                                <img src="{{ $shot }}" alt="" loading="lazy"
                                     class="aspect-video w-64 flex-none rounded-xl border border-gpn-border object-cover">
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($game->sysreq)
                    <section class="mt-10">
                        <h2 class="text-xl font-semibold">System Requirements</h2>
                        <hr class="my-4 border-gpn-border">
                        <dl class="space-y-3">
                            @foreach ($game->sysreq as $key => $value)
                                <div><dt class="inline font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                    <dd class="inline text-gpn-fg-muted">{{ $value }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                @endif

                @if ($game->iaps->isNotEmpty())
                    <section class="mt-10">
                        <h2 class="text-xl font-semibold">In-App Purchase</h2>
                        <hr class="my-4 border-gpn-border">
                        <ul class="space-y-3">
                            @foreach ($game->iaps as $iap)
                                <li class="flex items-center justify-between gap-4 rounded-xl border border-gpn-border p-4">
                                    <div>
                                        <p class="font-medium">{{ $iap->iap_nama }}</p>
                                        <p class="text-sm text-gpn-fg-faint">{{ $iap->deskripsi }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold">Rp {{ number_format($iap->iap_harga, 0, ',', '.') }}</span>
                                        <form method="POST" action="{{ route('checkout.store', ['type' => 'iap', 'id' => $iap->id]) }}">
                                            @csrf
                                            <button class="gpn-btn gpn-btn-primary gpn-btn-sm">Buy</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>

            {{-- SRS 3.1.3.1 Membeli Game / 3.1.3.2 Membeli IAP --}}
            <aside class="w-[280px] flex-none self-start rounded-2xl bg-gpn-raised p-6">
                <p class="text-3xl font-semibold">
                    {{ $game->game_harga > 0 ? 'Rp '.number_format($game->game_harga, 0, ',', '.') : 'Free' }}
                </p>

                @auth
                    @if (auth()->user()->sudahBeli($game))
                        <a href="{{ route('store.download', $game) }}" class="gpn-btn gpn-btn-primary mt-6 w-full">
                            <x-icon name="download" class="h-5 w-5" /> Download
                        </a>
                    @elseif ($game->game_harga > 0)
                        <form method="POST" action="{{ route('checkout.store', ['type' => 'game', 'id' => $game->id]) }}" class="mt-6">
                            @csrf
                            <button class="gpn-btn gpn-btn-primary w-full">Buy Now</button>
                        </form>
                    @else
                        <a href="{{ route('store.download', $game) }}" class="gpn-btn gpn-btn-primary mt-6 w-full">
                            <x-icon name="download" class="h-5 w-5" /> Download
                        </a>
                    @endif

                    <form method="POST" action="{{ route('store.wishlist', $game) }}" class="mt-3">
                        @csrf
                        <button class="gpn-btn gpn-btn-ghost w-full">
                            <x-icon name="heart" class="h-5 w-5" />
                            {{ $inWishlist ? 'In Wishlist' : 'Add to Cart' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="gpn-btn gpn-btn-primary mt-6 w-full">Sign in to buy</a>
                @endauth

                <dl class="mt-8 space-y-3 border-t border-gpn-border pt-6 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gpn-fg-muted">Developer</dt>
                        <dd class="truncate">{{ $game->developer?->nama_developer }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gpn-fg-muted">Studio</dt>
                        <dd class="truncate">{{ $game->developer?->nama_studio }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gpn-fg-muted">Rating</dt>
                        <dd class="inline-flex items-center gap-1">
                            <x-icon name="star" class="h-4 w-4 text-amber-400" /> {{ number_format($game->rating / 10, 1) }}
                        </dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</div>
@endsection
