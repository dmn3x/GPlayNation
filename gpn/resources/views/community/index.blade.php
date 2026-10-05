<x-app-layout title="Community">
    <div class="space-y-8">
        <section class="card bg-gradient-to-r from-accent to-accent-hi p-8 text-white md:p-10">
            <h1 class="screen-title">Community</h1>
            <p class="mt-2 text-[16.2px] opacity-90">Share updates and discover what local players are talking about.</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-[240px_1fr_280px]">
            <aside class="card h-fit space-y-3 p-5">
                <h2 class="section-heading">Trending</h2>
                @if (request('tag'))
                    <a href="{{ route('community.index') }}" class="text-sm text-accent-hi hover:underline">Clear filter: #{{ request('tag') }}</a>
                @endif
                @forelse ($trending as $tag => $count)
                    <a href="{{ route('community.index', ['tag' => $tag]) }}" @class(['flex items-center justify-between rounded px-2 py-1 text-[16.2px] hover:bg-elevated', 'bg-elevated font-semibold' => request('tag') === $tag])>
                        <span>#{{ $tag }}</span>
                        <span class="text-sm text-faint">{{ $count }}</span>
                    </a>
                @empty
                    <p class="text-sm text-subtle">No trending tags this week.</p>
                @endforelse
            </aside>

            <section class="space-y-4">
                @auth
                    <form action="{{ route('community.posts.store') }}" method="POST" enctype="multipart/form-data" class="card space-y-3 p-5">
                        @csrf
                        <textarea name="body" rows="3" placeholder="Share something with the community… (use #tags)" class="input" required>{{ old('body') }}</textarea>
                        @error('body')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <div class="flex items-center gap-3">
                            <input type="file" name="image" accept="image/*" class="text-sm text-subtle">
                            <button type="submit" class="btn-primary ml-auto !py-2">Post</button>
                        </div>
                        @error('image')
                            <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </form>
                @else
                    <div class="card flex items-center justify-between gap-4 p-5">
                        <p class="text-[16.2px] text-subtle">Join the conversation — sign in to post.</p>
                        <a href="{{ route('login') }}" class="btn-primary !py-2">Sign in</a>
                    </div>
                @endauth

                @forelse ($posts as $post)
                    <article class="card p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-accent text-sm font-semibold text-white">{{ strtoupper(substr($post->user->name, 0, 1)) }}</span>
                            <div>
                                <p class="font-medium">{{ $post->user->name }}</p>
                                <p class="text-sm text-faint">{{ $post->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="ml-auto flex items-center gap-1">
                                @php $myVote = auth()->check() ? $post->votes->where('user_id', auth()->id())->first()?->value : null; @endphp
                                @auth
                                    <form action="{{ route('posts.vote', $post) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="value" value="1">
                                        <button type="submit" aria-label="Upvote" @class(['rounded px-2 py-1 text-lg leading-none hover:bg-elevated', 'text-accent-hi font-bold' => $myVote === 1, 'text-faint' => $myVote !== 1])>▲</button>
                                    </form>
                                @endauth
                                <span class="min-w-6 text-center font-semibold">{{ $post->score }}</span>
                                @auth
                                    <form action="{{ route('posts.vote', $post) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="value" value="-1">
                                        <button type="submit" aria-label="Downvote" @class(['rounded px-2 py-1 text-lg leading-none hover:bg-elevated', 'text-accent-hi font-bold' => $myVote === -1, 'text-faint' => $myVote !== -1])>▼</button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                        <p class="mt-3 text-[16.2px]">{{ $post->body }}</p>
                        @if ($post->image_path)
                            <img src="{{ asset('storage/'.$post->image_path) }}" alt="Post attachment" loading="lazy" class="mt-3 max-h-96 w-full rounded object-cover">
                        @endif
                    </article>
                @empty
                    <div class="card px-8 py-16 text-center">
                        <p class="text-[16.2px] text-subtle">No posts yet. Be the first to share something.</p>
                    </div>
                @endforelse

                <div>{{ $posts->links() }}</div>
            </section>

            <aside class="card h-fit space-y-4 p-5">
                <h2 class="section-heading">Top this month</h2>
                @forelse ($topGames as $game)
                    <a href="{{ route('store.show', $game->slug) }}" class="flex items-center gap-3 rounded p-1 hover:bg-elevated">
                        <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} cover" loading="lazy" class="h-12 w-16 rounded object-cover bg-elevated">
                        <div class="min-w-0">
                            <p class="truncate font-medium">{{ $game->name }}</p>
                            <p class="text-sm text-faint">{{ $game->downloads_count }} downloads</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-subtle">No downloads yet this month.</p>
                @endforelse
            </aside>
        </div>
    </div>
</x-app-layout>
