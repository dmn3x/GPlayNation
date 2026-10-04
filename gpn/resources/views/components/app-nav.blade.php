{{-- Top navigation from Figma frames Home / Community / Profile --}}
<header class="gpn-nav flex-none border-b border-gpn-border-strong">
    <a href="{{ route('home') }}" class="mr-auto flex items-center" aria-label="GPlayNation home">
        <x-logo />
    </a>

    @php $nav = [
        ['route' => 'store.index', 'label' => 'Store', 'match' => 'store'],
        ['route' => 'community.index', 'label' => 'Community', 'match' => 'community'],
        ['route' => 'profile.show', 'label' => 'Profile', 'match' => 'profile'],
    ]; @endphp

    <nav class="flex items-center gap-2" aria-label="Primary">
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}"
               class="gpn-nav-link"
               @if (request()->routeIs($item['match'].'*')) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <form action="{{ route('store.index') }}" method="GET" role="search" class="ml-8 hidden lg:block">
        <label for="global-search" class="sr-only">Search games</label>
        <input id="global-search" type="search" name="q" value="{{ request('q') }}"
               placeholder="Search..." class="gpn-input w-72 rounded-full py-2.5">
    </form>

    <div class="ml-4 flex items-center gap-3">
        @auth
            <a href="{{ route('developer.dashboard') }}" class="gpn-btn gpn-btn-quiet gpn-btn-sm">
                <x-icon name="chart" class="h-4 w-4" /> Dev Tools
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="gpn-btn gpn-btn-quiet gpn-btn-sm">
                    <x-icon name="logout" class="h-4 w-4" /> Logout
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="gpn-btn gpn-btn-ghost">Sign In</a>
            <a href="{{ route('register') }}" class="gpn-btn gpn-btn-primary">Sign Up</a>
        @endauth
    </div>
</header>
