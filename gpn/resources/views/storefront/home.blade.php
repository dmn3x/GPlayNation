<x-app-layout title="Home">
    <div class="space-y-12">
        @if ($hero)
            <section class="card overflow-hidden">
                <div class="grid md:grid-cols-2">
                    <div class="aspect-video bg-elevated">
                        <img src="{{ asset('storage/'.$hero->cover_path) }}" alt="{{ $hero->name }} cover" class="h-full w-full object-cover">
                    </div>
                    <div class="flex flex-col justify-center gap-4 p-8 md:p-10">
                        <span class="w-fit rounded bg-accent/10 px-2 py-1 text-xs font-medium uppercase tracking-wide text-accent-hi">Featured</span>
                        <h1 class="screen-title">{{ $hero->name }}</h1>
                        <p class="line-clamp-3 text-[16.2px] text-muted">{{ $hero->description }}</p>
                        <div class="text-[18.9px]"><x-price :game="$hero" /></div>
                        <div>
                            <a href="{{ route('store.show', $hero->slug) }}" class="btn-primary w-fit">View game</a>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($popular->isNotEmpty())
            <section>
                <h2 class="section-heading mb-4">Popular</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($popular as $game)
                        <x-game-card :game="$game" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($latest->isNotEmpty())
            <section>
                <h2 class="section-heading mb-4">New Releases</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($latest as $game)
                        <x-game-card :game="$game" />
                    @endforeach
                </div>
            </section>
        @endif

        @if (! $hero && $popular->isEmpty() && $latest->isEmpty())
            <section class="card flex flex-col items-center gap-3 px-8 py-16 text-center">
                <x-icon name="gamepad" class="h-10 w-10 text-faint" />
                <h2 class="section-heading">No games yet</h2>
                <p class="text-[16.2px] text-subtle">Games published by local studios will appear here.</p>
            </section>
        @endif
    </div>

    @if ($showIntro)
        <div id="intro-overlay" class="overlay-leave fixed inset-0 z-50 grid place-items-center bg-bg" role="presentation" aria-hidden="true" onclick="this.remove()">
            <div class="flex items-center gap-4">
                <span class="logo-glow inline-flex"><x-logo class="h-16 w-16" /></span>
                <span class="overlay-enter text-[27px] font-semibold tracking-[-0.675px]">GPlayNation</span>
            </div>
        </div>
        <script>
            (function () {
                var overlay = document.getElementById('intro-overlay');
                if (! overlay) return;
                overlay.addEventListener('animationend', function (event) {
                    if (event.target === overlay) overlay.remove();
                });
            })();
        </script>
    @endif

    @if (session('account_created'))
        <div id="account-created" class="overlay-leave pointer-events-none fixed inset-0 z-50 flex items-center justify-center bg-bg/80 backdrop-blur-sm" role="status">
            <div class="card overlay-enter flex flex-col items-center gap-3 px-8 py-7 text-center">
                <x-icon name="check" class="h-10 w-10 text-success" />
                <p class="section-heading">Account created!</p>
                @if (session('account_created') === 'developer')
                    <p class="text-[16.2px] text-subtle">Your studio is awaiting admin verification.</p>
                @endif
            </div>
        </div>
        <script>
            (function () {
                var accountCreated = document.getElementById('account-created');
                if (! accountCreated) return;
                accountCreated.addEventListener('animationend', function () { accountCreated.remove(); });
            })();
        </script>
    @endif
</x-app-layout>
