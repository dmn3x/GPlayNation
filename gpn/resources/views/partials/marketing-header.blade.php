@php $active = $active ?? 'store'; @endphp
@php
    $items = [
        ['key' => 'store', 'label' => 'Store', 'route' => 'store.index'],
        ['key' => 'community', 'label' => 'Community', 'route' => 'community.index'],
        ['key' => 'profile', 'label' => 'Profile', 'route' => 'profile.show'],
    ];
@endphp
<header class="relative z-20 bg-gradient-to-b from-[#0F0F10]/90 via-[#0F0F10]/40 to-transparent">
    <div class="relative mx-auto flex h-[103px] max-w-[1510px] items-center justify-between px-12">
        <a href="{{ route('home') }}" class="flex items-center" aria-label="GPlayNation home">
            <x-logo class="h-8 w-auto" />
        </a>

        <nav class="absolute left-1/2 flex -translate-x-1/2 items-center gap-12">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   class="rounded-full px-4 py-2 text-[21px] font-medium backdrop-blur-md transition {{ $active === $item['key'] ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-6">
            <form action="{{ route('store.index') }}" method="GET">
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
                        <span class="text-[16px] font-medium">{{ auth()->user()->name }}</span>
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
    </div>
</header>
