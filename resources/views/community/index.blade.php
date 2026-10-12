<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Community · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    <div class="relative">
        <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute inset-0 bg-[#0F0F10]"></div>
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_70%_30%,rgba(29,78,216,0.35),transparent_60%)]"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-[#0F0F10] to-transparent"></div>
        </div>
        @include('partials.marketing-header', ['active' => 'community'])
    </div>

    <main class="mx-auto flex max-w-[1446px] flex-col items-stretch gap-5 px-4 py-6 lg:flex-row lg:items-start lg:px-8 lg:py-8">
        <aside class="hidden w-[260px] shrink-0 space-y-6 lg:block">
            <nav class="rounded-2xl border border-[#222224] bg-[#161618] p-3">
                @php
                    $items = [
                        ['label' => 'Home', 'icon' => 'home', 'active' => true],
                        ['label' => 'Popular', 'icon' => 'star', 'active' => false],
                        ['label' => 'Explore', 'icon' => 'search', 'active' => false],
                        ['label' => 'Achievements', 'icon' => 'shield', 'active' => false],
                        ['label' => 'Saved', 'icon' => 'heart', 'active' => false],
                    ];
                @endphp
                @foreach ($items as $item)
                    <a href="{{ route('community.index') }}"
                       @class(['flex h-[43px] items-center gap-3 rounded-xl px-3.5 text-[15px] font-medium transition',
                               'bg-[#1D4ED8]/15 text-white' => $item['active'],
                               'text-white/70 hover:bg-[#1D4ED8]/5 hover:text-white' => ! $item['active']])>
                        <x-icon :name="$item['icon']" class="h-[18px] w-[18px]" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="rounded-2xl border border-[#222224] bg-[#161618] p-4">
                <div class="mb-3.5 flex items-center justify-between">
                    <span class="text-[12px] text-white/50">My Groups</span>
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-white/5 text-white/60"><x-icon name="plus" class="h-3.5 w-3.5" /></span>
                </div>
                <div class="space-y-2.5">
                    @foreach ([['Ananta\'s Quest', '412K members'], ['Cosmic Drift', '208K members'], ['Forge of Echoes', '94K members'], ['Verdant Isles', '61K members']] as [$name, $members])
                        <a href="{{ route('community.index') }}" class="flex items-center gap-2.5 rounded-xl p-1.5 transition hover:bg-[#222224]">
                            <span class="h-9 w-9 rounded-lg bg-gradient-to-br from-[#1D4ED8] to-[#287BF7]"></span>
                            <span class="min-w-0">
                                <span class="block truncate text-[14px] font-medium text-white">{{ $name }}</span>
                                <span class="block text-[12px] font-medium text-white/50">{{ $members }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1D4ED8] to-[#0F0F10] p-6">
                <div class="absolute -right-8 -top-8 h-[120px] w-[120px] rounded-full bg-white/10"></div>
                <h3 class="text-[16px] font-medium text-white">Create a Group</h3>
                <p class="mt-1.5 text-[13px] leading-[18px] text-white/70">Start a community around your favorite world.</p>
                <a href="{{ route('community.index') }}" class="mt-4 inline-flex h-9 items-center rounded-full bg-white px-6 text-[13px] font-medium text-[#1D4ED8]">Get Started</a>
            </div>
        </aside>

        <section class="min-w-0 flex-1 space-y-5">
            @auth
                <form action="{{ route('community.posts.store') }}" method="POST" enctype="multipart/form-data" class="rounded-2xl border border-[#222224] bg-[#161618] p-4">
                    @csrf
                    <div class="flex items-center gap-3">
                        <span class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-[#1D4ED8] to-[#287BF7]"></span>
                        <input name="body" value="{{ old('body') }}" placeholder="Share something with the community..." required
                               class="h-11 flex-1 rounded-full border border-[#27282A] bg-[#0F0F10] px-4 text-[15px] placeholder:text-white/40 focus:border-[#1D4ED8] focus:outline-none">
                    </div>
                    @error('body')<p class="mt-2 text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                    <div class="mt-3.5 flex items-center gap-1.5">
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1.5 text-[13px] font-medium text-white/60 transition hover:bg-[#222224]">
                            <x-icon name="upload" class="h-4 w-4" /> Image
                            <input type="file" name="image" accept="image/*" class="hidden">
                        </label>
                        @error('image')<p class="text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                        <button type="submit" class="ml-auto flex h-9 items-center rounded-full bg-[#1D4ED8] px-5 text-[14px] font-medium text-white transition hover:bg-[#287BF7]">Post</button>
                    </div>
                </form>
            @else
                <div class="flex items-center justify-between rounded-2xl border border-[#222224] bg-[#161618] p-5">
                    <p class="text-[15px] text-white/60">Join the conversation — sign in to post.</p>
                    <a href="{{ route('login') }}" class="rounded-full bg-[#1D4ED8] px-5 py-2 text-[14px] font-medium">Sign in</a>
                </div>
            @endauth

            <div class="flex items-center gap-3 overflow-x-auto rounded-2xl border border-[#222224] bg-[#161618] p-1.5 sm:gap-5">
                <span class="flex h-[33px] shrink-0 items-center gap-2.5 rounded-[10px] bg-[#1D4ED8] px-6 text-[14px] font-medium sm:px-9">Hot</span>
                <span class="shrink-0 text-[14px] font-medium text-white/60">New</span>
                <span class="shrink-0 text-[14px] font-medium text-white/60">Top</span>
                <span class="shrink-0 text-[14px] font-medium text-white/60">Rising</span>
                <span class="ml-auto hidden shrink-0 text-[13px] text-white/40 md:inline">Showing posts from your communities</span>
            </div>

            @forelse ($posts as $post)
                <article class="flex overflow-hidden rounded-2xl border border-[#222224] bg-[#161618]">
                    <div class="flex w-[52px] shrink-0 flex-col items-center gap-1 border-r border-[#222224] bg-[#131315] py-4">
                        @php $myVote = auth()->check() ? $post->votes->where('user_id', auth()->id())->first()?->value : null; @endphp
                        @auth
                            <form action="{{ route('posts.vote', $post) }}" method="POST">
                                @csrf
                                <input type="hidden" name="value" value="1">
                                <button type="submit" aria-label="Upvote" class="rounded-lg p-1 hover:bg-[#222224] {{ $myVote === 1 ? 'text-[#287BF7]' : 'text-white/60' }}"><x-icon name="arrow-up" class="h-5 w-5" /></button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" aria-label="Upvote" class="rounded-lg p-1 text-white/60 hover:bg-[#222224]"><x-icon name="arrow-up" class="h-5 w-5" /></a>
                        @endauth
                        <span class="text-[13px] text-white">{{ $post->score }}</span>
                        @auth
                            <form action="{{ route('posts.vote', $post) }}" method="POST">
                                @csrf
                                <input type="hidden" name="value" value="-1">
                                <button type="submit" aria-label="Downvote" class="rounded-lg p-1 hover:bg-[#222224] {{ $myVote === -1 ? 'text-[#287BF7]' : 'text-white/60' }}"><x-icon name="arrow-down" class="h-5 w-5" /></button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" aria-label="Downvote" class="rounded-lg p-1 text-white/60 hover:bg-[#222224]"><x-icon name="arrow-down" class="h-5 w-5" /></a>
                        @endauth
                    </div>

                    <div class="min-w-0 flex-1 p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-2 text-[13px]">
                            <span class="flex h-[22px] w-[22px] items-center justify-center rounded-full bg-gradient-to-br from-[#1D4ED8] to-[#287BF7] text-[10px] font-semibold">{{ strtoupper(mb_substr($post->user->name, 0, 1)) }}</span>
                            <span class="text-white">{{ $post->user->name }}</span>
                            <span class="text-white/40">·</span>
                            <span class="text-white/50">{{ $post->created_at->diffForHumans() }}</span>
                            @if ($post->hashtags)
                                <span class="rounded-full bg-[#1D4ED8]/15 px-2 py-0.5 text-[11px] text-[#60A5FA]">#{{ $post->hashtags[0] ?? '' }}</span>
                            @endif
                        </div>
                        <p class="mt-3 text-[15px] leading-[22px] text-white/70">{{ $post->body }}</p>
                        @if ($post->image_path)
                            <div class="mt-3.5 overflow-hidden rounded-xl border border-[#222224]">
                                <img src="{{ asset('storage/'.$post->image_path) }}" alt="Post attachment" loading="lazy" class="max-h-96 w-full object-cover">
                            </div>
                        @endif
                        <div class="mt-3.5 flex flex-wrap items-center gap-1">
                            <span class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[13px] font-medium text-white/60 transition hover:bg-[#222224]"><x-icon name="users" class="h-4 w-4" /> Comments</span>
                            <span class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[13px] font-medium text-white/60 transition hover:bg-[#222224]"><x-icon name="upload" class="h-4 w-4" /> Share</span>
                            <span class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[13px] font-medium text-white/60 transition hover:bg-[#222224]"><x-icon name="heart" class="h-4 w-4" /> Save</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-[#222224] bg-[#161618] px-8 py-16 text-center text-white/60">
                    No posts yet. Be the first to share something.
                </div>
            @endforelse

            <div>{{ $posts->links() }}</div>
        </section>

        <aside class="hidden w-[320px] shrink-0 space-y-5 xl:block">
            <div class="rounded-2xl border border-[#222224] bg-[#161618] p-4">
                <h3 class="mb-3 text-[12px] font-semibold uppercase tracking-wide text-white/50">TRENDING NOW</h3>
                @forelse ($trending->take(5) as $tag => $count)
                    <a href="{{ route('community.index', ['tag' => $tag]) }}" class="flex items-center gap-3 rounded-lg px-2 py-1.5 transition hover:bg-white/5">
                        <span class="w-4 shrink-0 text-[12px] text-white/40">{{ $loop->iteration }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[14px] font-medium text-white">#{{ $tag }}</span>
                            <span class="block text-[12px] text-white/40">{{ $count }} posts</span>
                        </span>
                    </a>
                @empty
                    <p class="text-sm text-white/50">No trending tags this week.</p>
                @endforelse
            </div>

            <div class="rounded-2xl border border-[#222224] bg-[#161618] p-4">
                <h3 class="mb-3 text-[12px] font-semibold uppercase tracking-wide text-white/50">UPCOMING EVENTS</h3>
                @foreach ([['JUN', '12', 'Summer Showcase', 'Maple Studios'], ['JUN', '18', 'Forge Tournament Finals', 'Forge of Echoes'], ['JUN', '24', 'Community Q&A', 'Devs']] as [$month, $day, $title, $host])
                    <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                        <span class="grid h-11 w-10 shrink-0 place-items-center rounded-lg border border-[#27282A] bg-[#0F0F10] text-center">
                            <span><span class="block text-[10px] font-semibold text-white/50">{{ $month }}</span><span class="block text-[16px] font-bold leading-none text-white">{{ $day }}</span></span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-[14px] font-medium text-white">{{ $title }}</span>
                            <span class="block text-[12px] text-white/40">{{ $host }}</span>
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl border border-[#222224] bg-[#161618] p-4">
                <h3 class="mb-3 text-[12px] font-semibold uppercase tracking-wide text-white/50">POPULAR COMMUNITIES</h3>
                @forelse ($topGames as $topGame)
                    <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                        @if ($topGame->cover_path)
                            <img src="{{ asset('storage/'.$topGame->cover_path) }}" alt="" aria-hidden="true" class="h-8 w-8 shrink-0 rounded-lg object-cover">
                        @else
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gradient-to-br from-[#1D4ED8] to-[#287BF7] text-[12px] font-semibold text-white">{{ strtoupper(mb_substr($topGame->name, 0, 1)) }}</span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[14px] font-medium text-white">{{ $topGame->name }}</span>
                            <span class="block text-[12px] text-white/40">{{ $topGame->genre }}</span>
                        </span>
                        <button type="button" data-join-soon class="h-7 shrink-0 rounded-full border border-[#27282A] px-3 text-[12px] font-medium text-white/70 transition hover:bg-white/10 hover:text-white">Join</button>
                    </div>
                @empty
                    <p class="text-sm text-white/50">No popular communities yet.</p>
                @endforelse
            </div>
        </aside>
        <div id="join-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
            <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[14px] text-white shadow-lg">Group memberships are coming soon.</p>
        </div>
        <script>
            (function () {
                var note = document.getElementById('join-soon');
                var timer = null;
                document.querySelectorAll('[data-join-soon]').forEach(function (btn) {
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
    </main>
    <x-toasts />
</body>
</html>
