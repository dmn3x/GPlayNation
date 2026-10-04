@extends('layouts.app')
@section('title', 'GPlayNation — Marketplace Game Lokal Indonesia')

@section('content')
{{-- Figma: Home frame 6:1427 — full-bleed hero over the game artwork --}}
@if ($featured)
<section class="relative isolate overflow-hidden">
    <img src="{{ $featured->cover }}" alt=""
         class="absolute inset-0 -z-10 h-full w-full object-cover opacity-45">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-gpn-bg via-gpn-bg/85 to-transparent"></div>

    <div class="mx-auto max-w-[860px] px-12 py-24">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gpn-accent-hi">
            Game of the Week
        </p>
        <h1 class="mt-5 text-6xl font-semibold leading-tight text-white">
            '{{ $featured->game_nama }}'
        </h1>
        <p class="mt-2 text-2xl text-gpn-fg-muted">
            by {{ $featured->developer?->nama_studio ?? $featured->developer?->nama_developer }}
        </p>
        <p class="mt-10 max-w-[70ch] text-lg leading-relaxed text-gpn-fg-muted">
            {{ \Illuminate\Support\Str::limit($featured->deskripsi, 220) }}
        </p>
        <a href="{{ route('store.show', $featured) }}" class="gpn-btn gpn-btn-primary mt-12 px-8 py-4">
            Explore Now
        </a>
    </div>
</section>
@endif

<section class="mx-auto max-w-[1512px] px-12 pb-16">
    <div class="flex items-end justify-between">
        <h2 class="text-2xl font-semibold">Popular right now</h2>
        <a href="{{ route('store.index') }}" class="text-sm text-gpn-link hover:underline">See all</a>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($popular as $game)
            <x-game-card :game="$game" />
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-[1512px] px-12 pb-24">
    <h2 class="text-2xl font-semibold">Latest releases</h2>
    <div class="mt-6 grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-4">
        @foreach ($latest as $game)
            <x-game-card :game="$game" />
        @endforeach
    </div>
</section>
@endsection
