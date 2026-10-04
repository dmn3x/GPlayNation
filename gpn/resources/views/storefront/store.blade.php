<x-app-layout title="Store">
    <div class="mb-6">
        <h1 class="screen-title">Store</h1>
        <p class="text-[16.2px] text-muted">Browse games from local studios.</p>
    </div>

    <div class="flex flex-col gap-8 lg:flex-row">
        <aside class="lg:w-[220px] lg:shrink-0">
            <form method="GET" action="{{ route('store.index') }}" class="card space-y-6 p-4">
                @if (request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif

                <div>
                    <label for="q" class="mb-2 block text-sm font-medium text-muted">Search</label>
                    <input id="q" name="q" value="{{ request('q') }}" placeholder="Keyword…" class="input !py-2">
                </div>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-muted">Genre</legend>
                    <div class="space-y-1.5">
                        <label class="flex cursor-pointer items-center gap-2 text-[16.2px] text-subtle">
                            <input type="radio" name="genre" value="" @checked(! request('genre')) onchange="this.form.submit()" class="h-4 w-4 border-border-strong bg-elevated accent-[#1D4ED8]">
                            All genres
                        </label>
                        @foreach ($genres as $genre)
                            <label class="flex cursor-pointer items-center gap-2 text-[16.2px] text-subtle">
                                <input type="radio" name="genre" value="{{ $genre }}" @checked(request('genre') === $genre) onchange="this.form.submit()" class="h-4 w-4 border-border-strong bg-elevated accent-[#1D4ED8]">
                                {{ $genre }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-muted">Price</legend>
                    <div class="space-y-1.5">
                        <label class="flex cursor-pointer items-center gap-2 text-[16.2px] text-subtle">
                            <input type="radio" name="price" value="" @checked(! request('price')) onchange="this.form.submit()" class="h-4 w-4 border-border-strong bg-elevated accent-[#1D4ED8]">
                            Any price
                        </label>
                        @foreach (['free' => 'Free', 'under50' => 'Under Rp 50rb', '50to150' => 'Rp 50–150rb', 'above150' => 'Above Rp 150rb'] as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 text-[16.2px] text-subtle">
                                <input type="radio" name="price" value="{{ $value }}" @checked(request('price') === $value) onchange="this.form.submit()" class="h-4 w-4 border-border-strong bg-elevated accent-[#1D4ED8]">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-muted">Tags</legend>
                    @php $withoutTag = request()->except('tag', 'page'); @endphp
                    @if ($tags->isEmpty())
                        <p class="text-sm text-faint">No tags yet.</p>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($tags as $tag)
                                @php $tagActive = request('tag') === $tag->name; @endphp
                                <a href="{{ route('store.index', $tagActive ? $withoutTag : array_merge($withoutTag, ['tag' => $tag->name])) }}"
                                   class="rounded px-2.5 py-1 text-sm transition {{ $tagActive ? 'bg-accent text-white' : 'bg-elevated text-subtle hover:text-fg' }}">#{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </fieldset>

                <div class="space-y-2">
                    <button type="submit" class="btn-primary w-full">Apply filters</button>
                    @if (request()->hasAny(['q', 'genre', 'tag', 'price']))
                        <a href="{{ route('store.index') }}" class="block text-center text-sm text-subtle transition hover:text-fg">Clear all</a>
                    @endif
                </div>
            </form>
        </aside>

        <section class="min-w-0 flex-1">
            <div class="mb-4 flex items-center justify-between gap-4">
                <p class="text-[16.2px] text-muted">{{ $games->total() }} {{ $games->total() === 1 ? 'game' : 'games' }}</p>
                <form method="GET" action="{{ route('store.index') }}">
                    @foreach (request()->except('sort', 'page') as $key => $value)
                        @if (is_string($value) && $value !== '')
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <select name="sort" onchange="this.form.submit()" class="input !w-auto !py-2" aria-label="Sort games">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: low to high</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: high to low</option>
                        <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                    </select>
                </form>
            </div>

            @if ($games->isEmpty())
                <div class="card flex flex-col items-center gap-3 px-8 py-16 text-center">
                    <x-icon name="search" class="h-10 w-10 text-faint" />
                    <h2 class="section-heading">No games found</h2>
                    <p class="text-[16.2px] text-subtle">Try a different search or clear your filters.</p>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($games as $game)
                        <x-game-card :game="$game" />
                    @endforeach
                </div>
                <div class="mt-6">{{ $games->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
