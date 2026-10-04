<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @keyframes account-created {
            0% { opacity: 0; transform: scale(.97); }
            12% { opacity: 1; transform: scale(1); }
            82% { opacity: 1; }
            100% { opacity: 0; }
        }
        #account-created { animation: account-created 2.4s ease-in-out forwards; }
        @media (prefers-reduced-motion: reduce) {
            #account-created { animation: none; }
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-bg px-6 py-12">
    <div class="w-full max-w-[440px]">
        <div class="mb-8 flex flex-col items-center gap-4 text-center">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="GPlayNation home">
                <x-logo class="h-9 w-9" />
                <span class="text-[18.9px] font-semibold">GPlayNation</span>
            </a>
            <h1 class="screen-title">Welcome back</h1>
        </div>

        <form method="POST" action="{{ route('login') }}" class="card p-8">
            @csrf

            <div class="mb-5">
                <label for="email" class="mb-2 block text-[16.2px] font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" placeholder="budi@example.com" autocomplete="email">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="password" class="mb-2 block text-[16.2px] font-medium">Password</label>
                <input id="password" type="password" name="password" class="input" placeholder="••••••••" autocomplete="current-password">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6 flex items-center gap-3">
                <input id="remember" type="checkbox" name="remember" value="on" class="h-4 w-4 rounded border-border-strong bg-elevated accent-[#1D4ED8]" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" class="text-[16.2px] text-subtle">Remember me</label>
            </div>

            <button type="submit" class="btn-primary w-full">Sign in</button>
        </form>

        <p class="mt-6 text-center text-[16.2px] text-subtle">New here?
            <a href="{{ route('register') }}" class="font-medium text-link hover:underline">Create an account</a>
        </p>
    </div>

    @if (session('account_created'))
        <div id="account-created" class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center bg-bg/80 backdrop-blur-sm" role="status">
            <div class="card flex flex-col items-center gap-3 px-8 py-7 text-center">
                <x-icon name="check" class="h-10 w-10 text-success" />
                <p class="section-heading">Account created!</p>
                @if (session('account_created') === 'developer')
                    <p class="text-[16.2px] text-subtle">Your studio is awaiting admin verification.</p>
                @endif
            </div>
        </div>
    @endif

    @if (session('account_created'))
        <script>
            var accountCreated = document.getElementById('account-created');
            accountCreated.addEventListener('animationend', function () { accountCreated.remove(); });
        </script>
    @endif

    <x-toasts />
</body>
</html>
