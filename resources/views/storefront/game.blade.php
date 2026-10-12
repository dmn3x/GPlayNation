<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $game->name }} · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    <div class="flex flex-col lg:flex-row">
        <aside class="hidden w-[192px] shrink-0 flex-col border-r border-[#222224] bg-bg p-4 lg:sticky lg:top-0 lg:flex lg:h-screen">
            <a href="{{ route('home') }}" class="mb-6 flex items-center p-2" aria-label="GPlayNation home">
                <x-logo class="h-7 w-auto" />
            </a>
            <nav class="flex flex-col gap-1">
                @php
                    $nav = [
                        ['label' => 'Home', 'icon' => 'home', 'url' => route('home'), 'active' => false],
                        ['label' => 'Categories', 'icon' => 'tags', 'url' => route('store.index'), 'active' => false],
                        ['label' => 'Price', 'icon' => 'banknote', 'url' => route('store.index', ['sort' => 'price_asc']), 'active' => false],
                        ['label' => 'New Releases', 'icon' => 'gamepad', 'url' => route('store.index', ['sort' => 'newest']), 'active' => false],
                        ['label' => 'Recent All', 'icon' => 'document', 'url' => route('store.index'), 'active' => false],
                    ];
                @endphp
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}"
                       @class(['flex h-9 items-center gap-3 rounded-xl px-3 text-[14px] font-medium transition',
                               'bg-[#1D4ED8]/10 text-[#1D4ED8]' => $item['active'],
                               'text-white/60 hover:bg-white/5 hover:text-white' => ! $item['active']])>
                        <x-icon :name="$item['icon']" class="h-4 w-4" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <main class="min-w-0 flex-1">
            <div class="flex items-center gap-3 overflow-x-auto border-b border-[#222224] p-3 lg:hidden">
                <a href="{{ route('home') }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm text-white/60 hover:bg-white/5 hover:text-white">Home</a>
                <a href="{{ route('store.index') }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm text-white/60 hover:bg-white/5 hover:text-white">Categories</a>
                <a href="{{ route('store.index', ['sort' => 'price_asc']) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm text-white/60 hover:bg-white/5 hover:text-white">Price</a>
                <a href="{{ route('store.index', ['sort' => 'newest']) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-sm text-white/60 hover:bg-white/5 hover:text-white">New Releases</a>
            </div>
            <section class="relative h-[220px] overflow-hidden sm:h-[256px]">
                <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} banner" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0F0F10] via-[#0F0F10]/50 to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4 sm:bottom-8 sm:left-8 sm:right-auto">
                    <h1 class="text-[24px] font-bold leading-[30px] text-white sm:text-[32px] sm:leading-[40px]">{{ $game->name }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-white/70 sm:text-[14px]">
                        <span class="flex items-center gap-1"><x-icon name="gamepad" class="h-4 w-4" /> Windows, Mac</span>
                        <span>{{ $game->genre }}</span>
                        <span>{{ $game->created_at?->format('M j, Y') }}</span>
                    </div>
                </div>
            </section>

            <div class="flex flex-col gap-6 p-4 sm:p-6 lg:flex-row lg:items-start lg:gap-8 lg:p-8">
                <div class="min-w-0 flex-1 space-y-6 lg:space-y-8">
                    <section>
                        <h2 class="text-[18px] font-semibold leading-[28px] text-white">Description</h2>
                        <p class="mt-3 text-[14px] leading-[22px] text-white/70">{{ $game->description }}</p>
                    </section>

                    @if ($game->screenshots->isNotEmpty())
                        <section>
                            <h2 class="text-[18px] font-semibold leading-[28px] text-white">Media Carousel</h2>
                            <div class="mt-3 flex gap-4 overflow-x-auto pb-2">
                                @foreach ($game->screenshots as $screenshot)
                                    <div class="h-[112px] w-[200px] shrink-0 overflow-hidden rounded-xl border border-[#222224] bg-[#161618]">
                                        <img src="{{ asset('storage/'.$screenshot->path) }}" alt="{{ $game->name }} screenshot" class="h-full w-full object-cover opacity-70">
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section>
                        <h2 class="text-[18px] font-semibold leading-[28px] text-white">System Requirements</h2>
                        <ul class="mt-3 space-y-2 pt-3 text-[14px] leading-[20px] text-white/70">
                            <li>OS: Windows 10/11 64-bit</li>
                            <li>Processor: Intel Core i5-8400 / AMD Ryzen 5 2600</li>
                            <li>Memory: 16 GB RAM</li>
                            <li>Graphics: NVIDIA GeForce GTX 1060 / AMD Radeon RX 580</li>
                            <li>Storage: 45 GB available space</li>
                        </ul>
                    </section>

                    @if ($iaps->isNotEmpty())
                        <section>
                            <h2 class="text-[18px] font-semibold leading-[28px] text-white">In-app purchases</h2>
                            <div class="mt-3 space-y-3">
                                @foreach ($iaps as $iap)
                                    <div class="flex items-center justify-between rounded-2xl border border-[#222224] bg-[#161618] p-4">
                                        <div>
                                            <p class="font-medium">{{ $iap->name }}</p>
                                            <p class="text-xs text-white/50">{{ ucfirst($iap->type->value) }}@if ($iap->description) · {{ $iap->description }}@endif</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-semibold">{{ \App\Support\Money::format($iap->price) }}</span>
                                            <form method="POST" action="{{ route('checkout.store', ['iap', $iap->id]) }}">@csrf<button class="rounded-xl border border-[#27282A] bg-[#222224] px-4 py-2 text-sm">Buy</button></form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="w-full shrink-0 rounded-2xl border border-[#222224] bg-[#161618] p-5 sm:p-6 lg:w-[288px]">
                    <div class="text-[30px] font-bold leading-[36px] text-white"><x-price :game="$game" /></div>

                    <div class="mt-6 space-y-3">
                        <div class="flex h-[42px] items-center justify-between rounded-2xl border border-[#222224] bg-[#0F0F10] px-4 text-[14px]">
                            <span class="text-white/80">Standard Edition</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-white/40" />
                        </div>
                        <button type="button" data-cart-soon class="flex h-[42px] w-full items-center justify-center rounded-2xl border border-[#27282A] text-[14px] font-medium text-white transition hover:bg-[#222224]">
                            Add to Cart
                        </button>
                        @if ($inLibrary || $game->isFree())
                            <a href="{{ route('games.download', $game->slug) }}"
                               class="flex h-[42px] items-center justify-center rounded-2xl bg-[#1D4ED8] text-[14px] font-medium text-white transition hover:brightness-110">Download</a>
                        @else
                            <button type="button" data-open-buy class="flex h-[42px] w-full items-center justify-center rounded-2xl bg-[#1D4ED8] text-[14px] font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.3)] transition hover:brightness-110 hover:shadow-[0_0_30px_rgba(29,78,216,0.55)]">Buy Now</button>
                        @endif
                        @auth
                            <form method="POST" action="{{ route('wishlist.toggle', $game) }}">
                                @csrf
                                <button class="flex h-[42px] w-full items-center justify-center rounded-2xl border border-[#27282A] text-[14px] font-medium transition hover:bg-[#222224]">
                                    {{ $wishlisted ? 'Remove from Wishlist' : 'Add to Wishlist' }}
                                </button>
                            </form>
                        @endauth
                    </div>

                    <dl class="mt-6 space-y-2 border-t border-[#27282A] pt-4 text-[12px]">
                        <div class="flex justify-between"><dt class="text-white/60">Developer</dt><dd class="text-white">{{ $game->developer?->studio_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-white/60">Publisher</dt><dd class="text-white">{{ $game->developer?->studio_name ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-white/60">DRM</dt><dd class="text-white">DRM-Free</dd></div>
                    </dl>
                </aside>
            </div>
        </main>
    </div>
    @unless ($inLibrary || $game->isFree())
        <x-buy-modal :game="$game" />
    @endunless
    <div id="cart-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
        <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[14px] text-white shadow-lg">Cart is coming soon.</p>
    </div>
    <script>
        (function () {
            var note = document.getElementById('cart-soon');
            var timer = null;
            document.querySelectorAll('[data-cart-soon]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    note.classList.remove('hidden');
                    note.classList.add('flex');
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        note.classList.add('hidden');
                        note.classList.remove('flex');
                    }, 2500);
                });
            });
        })();
    </script>
    <x-toasts />
</body>
</html>
