{{-- Nav shown to signed-out visitors (Login / Register frames) --}}
<header class="gpn-nav flex-none border-b border-gpn-border-strong">
    <a href="{{ route('home') }}" class="mr-auto flex items-center" aria-label="GPlayNation home">
        <x-logo />
    </a>

    <nav class="flex items-center gap-2" aria-label="Primary">
        <a href="{{ route('store.index') }}" class="gpn-nav-link"
           @if (request()->routeIs('store.*')) aria-current="page" @endif>Store</a>
        <a href="{{ route('community.index') }}" class="gpn-nav-link"
           @if (request()->routeIs('community.*')) aria-current="page" @endif>Community</a>
    </nav>

    <div class="ml-auto flex items-center gap-3">
        @yield('nav-cta')
        <a href="{{ route('login') }}" class="gpn-btn gpn-btn-ghost">Sign In</a>
        <a href="{{ route('register') }}" class="gpn-btn gpn-btn-primary">Sign Up</a>
    </div>
</header>
