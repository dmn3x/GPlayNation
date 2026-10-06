@php $active = $active ?? 'store'; @endphp
@php
    $items = [
        ['key' => 'store', 'label' => 'Store', 'route' => 'store.index'],
        ['key' => 'community', 'label' => 'Community', 'route' => 'community.index'],
        ['key' => 'profile', 'label' => 'Profile', 'route' => 'profile.show'],
    ];
@endphp
<header class="relative z-20 bg-gradient-to-b from-[#0F0F10]/90 via-[#0F0F10]/40 to-transparent">
    <div class="relative mx-auto flex h-16 max-w-[1510px] items-center justify-between gap-3 px-4 md:h-[103px] md:px-12">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="GPlayNation home">
            <x-logo class="h-7 w-auto md:h-8" />
        </a>

        <nav class="absolute left-1/2 hidden -translate-x-1/2 items-center gap-12 lg:flex">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   class="rounded-full px-4 py-2 text-[21px] font-medium backdrop-blur-md transition {{ $active === $item['key'] ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-6 md:flex">
            <form action="{{ route('store.index') }}" method="GET" class="hidden xl:block">
                <label class="flex h-[51px] w-72 items-center gap-3 rounded-full border border-[#27282A] bg-[#161618] px-[18px]">
                    <x-icon name="search" class="h-6 w-6 shrink-0 text-white/40" />
                    <input name="q" value="{{ request('q') }}" placeholder="Search..."
                           class="w-full bg-transparent text-[21px] text-white placeholder:text-white/40 focus:outline-none">
                </label>
            </form>

            @auth
                <details class="relative">
                    <summary class="flex h-[51px] cursor-pointer list-none items-center gap-3 rounded-full border border-white/10 bg-white/10 px-5 [&::-webkit-details-marker]:hidden">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-semibold">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="hidden text-[16px] font-medium xl:inline">{{ auth()->user()->name }}</span>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-border bg-bg shadow-lg">
                        <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Profile</a>
                        <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Transactions</a>
                        @if (auth()->user()->isVerifiedDeveloper())
                            <a href="{{ route('dev.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Developer portal</a>
                        @endif
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-[15px] hover:bg-elevated">Admin panel</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-[15px] text-danger hover:bg-elevated">Log out</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="flex h-[51px] items-center rounded-full border border-white/10 bg-white/10 px-8 text-[21px] font-medium transition hover:bg-white/20">Sign In</a>
            @endauth
        </div>

        <details class="relative md:hidden">
            <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full border border-white/10 bg-white/10 [&::-webkit-details-marker]:hidden" aria-label="Open menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </summary>
            <div class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-xl border border-border bg-bg p-2 shadow-lg">
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}" class="block rounded-lg px-4 py-2.5 text-[15px] font-medium {{ $active === $item['key'] ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">{{ $item['label'] }}</a>
                @endforeach
                <form action="{{ route('store.index') }}" method="GET" class="mt-1 border-t border-border pt-2">
                    <label class="flex h-11 items-center gap-2 rounded-xl border border-[#27282A] bg-[#161618] px-3">
                        <x-icon name="search" class="h-5 w-5 shrink-0 text-white/40" />
                        <input name="q" value="{{ request('q') }}" placeholder="Search..." class="w-full bg-transparent text-[15px] text-white placeholder:text-white/40 focus:outline-none">
                    </label>
                </form>
                @auth
                    <div class="mt-1 border-t border-border pt-2">
                        <a href="{{ route('profile.show') }}" class="block rounded-lg px-4 py-2.5 text-[15px] hover:bg-elevated">Profile</a>
                        <a href="{{ route('transactions.index') }}" class="block rounded-lg px-4 py-2.5 text-[15px] hover:bg-elevated">Transactions</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-lg px-4 py-2.5 text-left text-[15px] text-danger hover:bg-elevated">Log out</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="mt-2 flex h-11 items-center justify-center rounded-xl bg-white/10 text-[15px] font-medium">Sign In</a>
                @endauth
            </div>
        </details>
    </div>
</header>
