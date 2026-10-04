@extends('layouts.app')
@section('title', 'Community — GPlayNation')

@section('content')
{{-- Figma: Community frame 181:529 --}}
<div class="mx-auto max-w-[1512px] px-8 py-8">
    <div class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)_300px]">

        {{-- left rail --}}
        <div class="space-y-5">
            <nav class="gpn-panel p-4">
                @php
                    $links = [
                        ['community.index', 'home', 'Home'],
                        ['community.index?sort=top', 'flame', 'Popular'],
                        ['community.index?sort=explore', 'compass', 'Explore'],
                        ['community.index', 'trophy', 'Achievements'],
                        ['community.index', 'bookmark', 'Saved'],
                    ];
                @endphp
                @foreach ($links as [$route, $icon, $label])
                    <a href="{{ is_string($route) && str_contains($route, '?') ? str_replace('community.index', route('community.index'), $route) : route($route) }}"
                       class="gpn-side-link">
                        <x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}
                        @if ($loop->first)<span class="ml-auto h-2 w-2 rounded-full bg-gpn-accent-hi"></span>@endif
                    </a>
                @endforeach
            </nav>

            <div class="gpn-panel p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gpn-fg-muted">My Groups</p>
                    <button class="gpn-stat-icon !h-7 !w-7"><x-icon name="plus" class="h-4 w-4" /></button>
                </div>
                <ul class="mt-4 space-y-3">
                    @foreach ($popularGames as $game)
                        <li>
                            <a href="{{ route('store.show', $game) }}" class="flex items-center gap-3">
                                <img src="{{ $game->cover }}" alt="" loading="lazy"
                                     class="h-9 w-9 rounded-lg object-cover">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium">{{ $game->game_nama }}</span>
                                    <span class="block text-xs text-gpn-fg-faint">{{ number_format($game->unduhan) }} members</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="gpn-card bg-gpn-accent p-5">
                <p class="text-lg font-medium">Create a Group</p>
                <p class="mt-1 text-sm text-gpn-fg-muted">Start a community around your favorite world.</p>
                <button class="gpn-btn mt-4 h-10 bg-white text-gpn-accent">Get Started</button>
            </div>
        </div>

        {{-- feed --}}
        <div class="min-w-0">
            @auth
                <form method="POST" action="{{ route('community.store') }}" class="gpn-panel p-5">
                    @csrf
                    <div class="flex gap-4">
                        <span class="gpn-stat-icon !h-11 !w-11 shrink-0">
                            <x-icon name="user" class="h-5 w-5" />
                        </span>
                        <label for="isi" class="sr-only">Share something with the community</label>
                        <textarea id="isi" name="isi" rows="2" required
                                  class="gpn-input resize-none rounded-full"
                                  placeholder="Share something with the community..."></textarea>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-4">
                        @foreach (['Image' => 'monitor', 'Clip' => 'bolt', 'Link' => 'download', 'Poll' => 'chart'] as $label => $icon)
                            <button type="button" class="inline-flex items-center gap-2 text-sm text-gpn-fg-muted hover:text-gpn-fg">
                                <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
                            </button>
                        @endforeach
                        <button class="gpn-btn gpn-btn-primary gpn-btn-sm ml-auto">Post</button>
                    </div>
                </form>
            @endauth

            <div class="gpn-panel mt-5 flex items-center gap-2 p-2">
                @foreach (['hot' => 'Hot', 'new' => 'New', 'top' => 'Top', 'rising' => 'Rising'] as $value => $label)
                    <a href="{{ route('community.index', request()->except('sort') + ['sort' => $value]) }}"
                       class="rounded-lg px-5 py-2 text-sm {{ request('sort', 'hot') === $value ? 'bg-gpn-accent text-white' : 'text-gpn-fg-muted hover:text-gpn-fg' }}">
                        {{ $label }}
                    </a>
                @endforeach
                <span class="ml-auto pr-4 text-sm text-gpn-fg-ghost">Showing posts from your communities</span>
            </div>

            @forelse ($posts as $post)
                <article class="gpn-panel mt-5 overflow-hidden">
                    <div class="flex">
                        <div class="flex w-16 flex-none flex-col items-center gap-2 border-r border-gpn-border py-4 text-xs text-gpn-fg-faint">
                            <button class="rounded p-1 hover:text-gpn-fg">▲</button>
                            <span class="font-medium">{{ number_format($post->skor / 1000, 1) }}k</span>
                            <button class="rounded p-1 hover:text-gpn-fg">▼</button>
                        </div>

                        <div class="min-w-0 flex-1 p-5">
                            <header class="flex flex-wrap items-center gap-2 text-sm">
                                <img src="{{ $post->pengguna->avatar ?? '' }}" alt="" onerror="this.style.visibility='hidden'"
                                     class="h-6 w-6 rounded-full bg-gpn-elevated object-cover">
                                <span class="font-medium">{{ $post->game?->game_nama ?? 'General' }}</span>
                                <span class="text-gpn-fg-faint">· Posted by {{ '@'.$post->pengguna->pengguna_nama }}</span>
                                <span class="text-gpn-fg-faint">· {{ $post->created_at->diffForHumans() }}</span>
                                <span class="gpn-tag ml-auto">{{ $post->jenis }}</span>
                            </header>

                            <p class="mt-3 text-lg leading-relaxed text-gpn-fg-muted">{{ $post->isi }}</p>

                            <footer class="mt-5 flex items-center gap-6 text-sm text-gpn-fg-muted">
                                <span class="inline-flex items-center gap-2"><x-icon name="users" class="h-4 w-4" /> {{ random_int(12, 900) }} Comments</span>
                                <span class="inline-flex items-center gap-2"><x-icon name="download" class="h-4 w-4" /> Share</span>
                                <span class="inline-flex items-center gap-2"><x-icon name="bookmark" class="h-4 w-4" /> Save</span>
                                @auth
                                    <form method="POST" action="{{ route('community.vote', $post) }}" class="ml-auto">
                                        @csrf
                                        <button class="text-gpn-link hover:underline">Vote</button>
                                    </form>
                                @endauth
                            </footer>
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state class="mt-5" icon="users" title="Belum ada kiriman"
                               message=" Jadilah yang pertama berbagi di komunitas GPlayNation." />
            @endforelse

            <div class="mt-8">{{ $posts->links() }}</div>
        </div>

        {{-- right rail --}}
        <aside class="space-y-5">
            <div class="gpn-panel p-5">
                <p class="text-sm font-medium uppercase tracking-wide text-gpn-fg-faint">Trending Now</p>
                <ol class="mt-4 space-y-3">
                    @forelse ($trending as $tag => $count)
                        <li class="flex items-center gap-3">
                            <span class="w-4 text-xs text-gpn-fg-ghost">{{ $loop->iteration }}</span>
                            <a href="{{ route('community.index', ['q' => $tag]) }}" class="text-sm font-medium hover:text-gpn-link">{{ $tag }}</a>
                            <span class="ml-auto text-xs text-gpn-fg-faint">{{ number_format($count, 1, ',', '.') }}k posts</span>
                        </li>
                    @empty
                        <li class="text-sm text-gpn-fg-faint">Belum ada tren.</li>
                    @endforelse
                </ol>
            </div>

            <div class="gpn-panel p-5">
                <p class="text-sm font-medium uppercase tracking-wide text-gpn-fg-faint">Popular Games</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($popularGames as $game)
                        <li class="flex items-center gap-3">
                            <a href="{{ route('store.show', $game) }}" class="flex min-w-0 flex-1 items-center gap-3">
                                <img src="{{ $game->cover }}" alt="" loading="lazy"
                                     class="h-9 w-9 rounded-lg object-cover">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium">{{ $game->game_nama }}</span>
                                    <span class="block text-xs text-gpn-fg-faint">{{ number_format($game->unduhan) }} members</span>
                                </span>
                            </a>
                            <a href="{{ route('store.show', $game) }}" class="gpn-btn gpn-btn-quiet gpn-btn-sm">Join</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
</div>
@endsection
