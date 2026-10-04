@extends('layouts.app')
@section('title', 'Store — GPlayNation')

@section('content')
{{-- Figma: Store frame 6:1698 — filter sidebar + Marketplace grid --}}
<div class="flex">
    <aside class="gpn-sidebar w-[220px] flex-none p-6" aria-label="Filters">
        <div class="flex items-center gap-2 font-medium">
            <x-icon name="filter" class="h-5 w-5" /> Filters
        </div>

        @php
            $groups = [
                'kategori' => ['label' => 'Categories', 'options' => $kategori],
                'genre' => ['label' => 'Genre', 'options' => $genres],
            ];
        @endphp

        @foreach ($groups as $param => $group)
            <details class="mt-6 group" open>
                <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                    {{ $group['label'] }}
                    <x-icon name="chevron-down" class="h-4 w-4 text-gpn-fg-faint transition group-open:rotate-180" />
                </summary>

                <div class="mt-3 space-y-2">
                    <a href="{{ route('store.index', request()->except($param)) }}"
                       class="block text-sm {{ request($param) === null ? 'text-gpn-accent-hi' : 'text-gpn-fg-muted hover:text-gpn-fg' }}">
                        All
                    </a>
                    @foreach ($group['options']->filter() as $option)
                        <a href="{{ route('store.index', array_merge(request()->query(), [$param => $option])) }}"
                           class="block text-sm {{ request($param) === $option ? 'text-gpn-accent-hi' : 'text-gpn-fg-muted hover:text-gpn-fg' }}">
                            {{ $option }}
                        </a>
                    @endforeach
                </div>
            </details>
        @endforeach

        <details class="mt-6 group" open>
            <summary class="flex cursor-pointer list-none items-center justify-between font-medium">
                Price
                <x-icon name="chevron-down" class="h-4 w-4 text-gpn-fg-faint transition group-open:rotate-180" />
            </summary>
            <div class="mt-3 space-y-2">
                @foreach (['gratis' => 'Free', 'murah' => 'Under Rp 100K', 'menengah' => 'Rp 100K – 300K', 'mahal' => 'Above Rp 300K'] as $value => $label)
                    <a href="{{ route('store.index', array_merge(request()->query(), ['harga' => $value])) }}"
                       class="block text-sm {{ request('harga') === $value ? 'text-gpn-accent-hi' : 'text-gpn-fg-muted hover:text-gpn-fg' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </details>

        <x-back-button :href="route('home')" class="mt-8 w-full" />
    </aside>

    <div class="min-w-0 flex-1 px-10 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-3xl font-semibold">Marketplace Home</h1>

            <form method="GET" class="flex items-center gap-3">
                @foreach (request()->except('sort') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="sort" class="sr-only">Sort by</label>
                <select id="sort" name="sort" onchange="this.form.submit()"
                        class="gpn-input w-auto rounded-full py-2.5">
                    @foreach (['recommended' => 'Recommended', 'terlaris' => 'Best selling', 'terbaru' => 'Newest', 'rating' => 'Top rated'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('sort', 'recommended') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($games->isEmpty())
            <x-empty-state class="mt-10" icon="search"
                           title="Game tidak ditemukan"
                           message="Coba kata kunci atau filter yang lain." />
        @else
            <div class="mt-8 grid grid-cols-2 gap-6 xl:grid-cols-3">
                @foreach ($games as $game)
                    <x-game-card :game="$game" />
                @endforeach
            </div>

            <div class="mt-10">{{ $games->links() }}</div>
        @endif
    </div>
</div>
@endsection
