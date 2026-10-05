<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    <div class="flex">
        <aside class="sticky top-0 h-screen w-[224px] shrink-0 border-r border-[#222224] bg-bg p-4">
            <div class="mb-6 flex items-center gap-2 p-1">
                <x-icon name="tags" class="h-4 w-4 text-white/90" />
                <span class="text-[14px] font-semibold text-white/90">Filters</span>
            </div>

            <form method="GET" action="{{ route('store.index') }}" id="store-filters">
                @if (request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                <div class="rounded-xl border border-[#222224] bg-[#161618] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-[14px] font-medium text-white/80">Categories</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-white/50" />
                    </div>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" name="genre" value="" class="peer sr-only" @checked(! request('genre')) onchange="this.form.submit()">
                            <span class="grid h-3.5 w-3.5 place-items-center rounded border border-[#27282A] bg-[#161618] peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]"></span>
                            <span class="text-[12px] font-medium text-white/60">All</span>
                        </label>
                        @foreach ($genres as $genre)
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="genre" value="{{ $genre }}" class="peer sr-only" @checked(request('genre') === $genre) onchange="this.form.submit()">
                                <span class="grid h-3.5 w-3.5 place-items-center rounded border border-[#27282A] bg-[#161618] peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]"></span>
                                <span class="text-[12px] font-medium text-white/60">{{ $genre }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-[#222224] bg-[#161618] p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-[14px] font-medium text-white/80">Price</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-white/50" />
                    </div>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="radio" name="price" value="" class="peer sr-only" @checked(! request('price')) onchange="this.form.submit()">
                            <span class="grid h-3.5 w-3.5 place-items-center rounded border border-[#27282A] bg-[#161618] peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]"></span>
                            <span class="text-[12px] font-medium text-white/60">Any</span>
                        </label>
                        @foreach (['free' => 'Free', 'under50' => 'Under Rp 50rb', '50to150' => 'Rp 50–150rb', 'above150' => 'Above Rp 150rb'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="price" value="{{ $value }}" class="peer sr-only" @checked(request('price') === $value) onchange="this.form.submit()">
                                <span class="grid h-3.5 w-3.5 place-items-center rounded border border-[#27282A] bg-[#161618] peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]"></span>
                                <span class="text-[12px] font-medium text-white/60">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-[#222224] bg-[#161618] p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] font-medium text-white/80">New Releases</span>
                        <x-icon name="chevron-down" class="h-4 w-4 text-white/50" />
                    </div>
                </div>
            </form>

            <a href="{{ route('home') }}" class="mt-4 flex h-[38px] items-center justify-center gap-3 rounded-xl border border-[#27282A] bg-[#161618] text-[14px] font-medium text-white/60 transition hover:text-white">
                <x-icon name="chevron-left" class="h-4 w-4" /> Back
            </a>
        </aside>

        <main class="min-w-0 flex-1 p-6">
            @if (request('q'))
                <div class="mb-6 rounded-2xl border border-[#222224] bg-[#161618] p-4">
                    <form action="{{ route('store.index') }}" method="GET" class="flex h-12 items-center gap-3 rounded-2xl border border-[#222224] bg-[#0F0F10] px-4">
                        @foreach (request()->except('q') as $key => $value)
                            @if (is_string($value) && $value !== '')
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <x-icon name="search" class="h-5 w-5 shrink-0 text-white/40" />
                        <input name="q" value="{{ request('q') }}" placeholder="Search games, genres…" class="w-full bg-transparent text-[14px] text-white placeholder:text-white/50 focus:outline-none">
                        <kbd class="rounded-md bg-white/10 px-2 py-0.5 text-xs text-white/60">⌘K</kbd>
                    </form>
                    <p class="mt-3 text-[14px] text-white/60">{{ $games->total() }} game{{ $games->total() === 1 ? '' : 's' }} to explore for &ldquo;{{ request('q') }}&rdquo;</p>
                </div>
            @endif
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-[24px] font-bold leading-[32px] text-white">Marketplace Home</h1>
                <form method="GET" action="{{ route('store.index') }}">
                    @foreach (request()->except('sort', 'page') as $key => $value)
                        @if (is_string($value) && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label class="flex h-[38px] items-center gap-2 rounded-xl border border-[#222224] bg-[#161618] px-4">
                        <span class="text-[14px] font-medium text-white/80">Sort by:</span>
                        <select name="sort" onchange="this.form.submit()" class="bg-transparent text-[14px] font-medium text-white/80 focus:outline-none">
                            <option value="newest" @selected(request('sort', 'newest') === 'newest')>Recommended</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                            <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                        </select>
                    </label>
                </form>
            </div>

            @if ($games->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-2xl border border-[#27282A] bg-[#161618] px-8 py-16 text-center">
                    <x-icon name="search" class="h-10 w-10 text-white/30" />
                    <h2 class="text-[16px] font-semibold">No games found</h2>
                    <p class="text-sm text-white/60">Try a different search or clear your filters.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
                    @foreach ($games as $game)
                        <a href="{{ route('store.show', $game->slug) }}" class="group block overflow-hidden rounded-2xl border border-[#222224] bg-[#161618] transition hover:border-[#303034]">
                            <div class="relative h-[128px] bg-black">
                                <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} cover" loading="lazy" class="h-full w-full object-cover opacity-80">
                                <div class="absolute left-2 top-2 flex h-6 items-center gap-1.5 rounded border border-white/10 bg-black/60 px-2">
                                    <span class="flex h-2 w-3 flex-col overflow-hidden rounded-[1px]">
                                        <span class="h-1/2 bg-[#E7000B]"></span>
                                        <span class="h-1/2 bg-white"></span>
                                    </span>
                                    <span class="text-[9px] font-bold uppercase tracking-wide text-white">Proudly Indonesian</span>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="truncate text-[14px] font-semibold text-white">{{ $game->name }}</h3>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded border border-[#222224] bg-[#0F0F10] px-1.5 py-0.5 text-[10px] text-white/50">{{ $game->genre }}</span>
                                    @foreach ($game->tags->take(1) as $tag)
                                        <span class="rounded border border-[#222224] bg-[#0F0F10] px-1.5 py-0.5 text-[10px] text-white/50">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                                <div class="mt-3 flex items-center justify-between border-t border-[#222224] pt-3">
                                    <span class="text-[14px] font-bold text-white"><x-price :game="$game" /></span>
                                    <span class="flex h-[34px] w-[65px] items-center justify-center rounded-[14px] bg-[#1D4ED8] text-[12px] font-medium text-white shadow-[0_0_15px_rgba(29,78,216,0.25)] transition group-hover:shadow-[0_0_30px_rgba(29,78,216,0.55)] group-hover:brightness-110">Buy</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-6">{{ $games->links() }}</div>
            @endif
        </main>
    </div>
    <x-toasts />
</body>
</html>
