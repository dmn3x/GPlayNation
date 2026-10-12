<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0F0F10] font-sans antialiased">
    <x-auth-shell wide="true">
        <div class="flex flex-col items-center">
            <div class="pb-[21.6px]">
                <a href="{{ route('home') }}" aria-label="GPlayNation home">
                    <x-logo class="h-[76px] w-auto text-white" />
                </a>
            </div>
            <h1 class="text-[27px] font-semibold leading-[37.8px] tracking-[-0.67px] text-white">Login</h1>
            <p class="pt-[5.4px] text-[16.2px] leading-[21.6px] text-white/50">Welcome back to GPlayNation</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-[21.6px] pt-[43.2px] pb-[32.4px]">
            @csrf

            <div>
                <label for="email" class="mb-[8.1px] block pl-[5.4px] text-[16.2px] font-medium leading-[24.3px] text-white/70">Email</label>
                <div class="relative">
                    <x-icon name="mail" class="absolute top-1/2 left-[16px] h-[21.6px] w-[21.6px] -translate-y-1/2 text-white/40" />
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="input-auth input-auth-lg" placeholder="name@example.com" autocomplete="email">
                </div>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-[8.1px] block pl-[5.4px] text-[16.2px] font-medium leading-[24.3px] text-white/70">Password</label>
                <div class="relative">
                    <x-icon name="lock" class="absolute top-1/2 left-[16px] h-[21.6px] w-[21.6px] -translate-y-1/2 text-white/40" />
                    <input id="password" type="password" name="password" class="input-auth input-auth-lg" placeholder="••••••••" autocomplete="current-password">
                </div>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn-auth btn-auth-lg">Login</button>
        </form>

        <div class="flex items-center" aria-hidden="true">
            <span class="h-px w-[131px] bg-[#222224]"></span>
            <span class="flex-1 text-center text-[16px] text-white/40">or continue with</span>
            <span class="h-px w-[131px] bg-[#222224]"></span>
        </div>

        <div class="flex gap-[15px] pt-[21.6px]">
            <button type="button" data-social="Google" class="social-btn social-btn-lg flex-1">
                <svg class="h-[21.6px] w-[21.6px] fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                <span>Google</span>
            </button>
            <button type="button" data-social="Apple" class="social-btn social-btn-lg flex-1">
                <svg class="h-[21.6px] w-[21.6px] fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"/></svg>
                <span>Apple</span>
            </button>
        </div>

        <p class="flex justify-center px-[21.6px] pt-[40.5px] text-[16.2px] leading-[21.6px] text-white/40">Don't have an account?&nbsp;<a href="{{ route('register') }}" class="text-[#1D4ED8] hover:underline">Sign Up</a></p>
    </x-auth-shell>

    <div id="social-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
        <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[16px] text-white shadow-lg"><span id="social-soon-name">Social</span> sign-in is coming soon.</p>
    </div>
    <script>
        (function () {
            var note = document.getElementById('social-soon');
            var label = document.getElementById('social-soon-name');
            var timer = null;
            document.querySelectorAll('[data-social]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    label.textContent = btn.getAttribute('data-social');
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

    @if (session('account_created'))
        <div id="account-created" class="overlay-leave pointer-events-none fixed inset-0 z-50 flex items-center justify-center bg-[#0F0F10]/80 backdrop-blur-sm" role="status">
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
                accountCreated.addEventListener('animationend', function (event) {
                    if (event.target !== accountCreated) return;
                    accountCreated.remove();
                });
            })();
        </script>
    @endif

    <x-toasts />
</body>
</html>
