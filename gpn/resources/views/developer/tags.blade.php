@extends('developer.layout')
@section('title', 'Tags — Developer Tools')

@section('dev')
<h1 class="text-3xl font-semibold">Tags</h1>
<p class="mt-1 text-gpn-fg-muted">Genre tags used across the marketplace catalog.</p>

<div class="mt-6 grid gap-4 md:grid-cols-2">
    <div class="gpn-panel p-6">
        <h2 class="font-medium">All genres</h2>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach ($genres as $genre)
                <span class="gpn-tag">{{ $genre }}</span>
            @endforeach
        </div>
    </div>

    <div class="gpn-panel p-6">
        <h2 class="font-medium">Usage</h2>
        <ul class="mt-4 space-y-3">
            @foreach ($usage as $row)
                <li class="flex items-center gap-3">
                    <span class="w-32 truncate text-sm">{{ $row->game_genre }}</span>
                    <span class="h-2 rounded-full bg-gpn-accent"
                          style="width: {{ max(4, $row->total * 12) }}px"></span>
                    <span class="ml-auto text-sm text-gpn-fg-faint">{{ $row->total }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
