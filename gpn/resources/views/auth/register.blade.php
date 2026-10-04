<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create your account · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-bg px-6 py-12">
    <div class="w-full max-w-[440px]">
        <div class="mb-8 flex flex-col items-center gap-4 text-center">
            <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="GPlayNation home">
                <x-logo class="h-9 w-9" />
                <span class="text-[18.9px] font-semibold">GPlayNation</span>
            </a>
            <h1 class="screen-title">Create your account</h1>
        </div>

        <form id="register-form" method="POST" action="{{ route('register') }}" class="card p-8">
            @csrf

            {{-- Account type toggle --}}
            <div class="mb-6">
                <p class="mb-2 text-[16.2px] font-medium">Account type</p>
                <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="Account type">
                    <label>
                        <input type="radio" name="account_type" value="user" class="peer sr-only" {{ old('account_type', 'user') === 'user' ? 'checked' : '' }}>
                        <span class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-border-strong bg-elevated px-4 py-3.5 transition peer-checked:border-accent peer-checked:bg-accent/10 peer-focus-visible:shadow-[0_0_0_1.67px_#1D4ED8]">
                            <x-icon name="gamepad" class="h-6 w-6" />
                            <span class="text-[16.2px] font-medium">Gamer</span>
                        </span>
                    </label>
                    <label>
                        <input type="radio" name="account_type" value="developer" class="peer sr-only" {{ old('account_type', 'user') === 'developer' ? 'checked' : '' }}>
                        <span class="flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-border-strong bg-elevated px-4 py-3.5 transition peer-checked:border-accent peer-checked:bg-accent/10 peer-focus-visible:shadow-[0_0_0_1.67px_#1D4ED8]">
                            <x-icon name="cog" class="h-6 w-6" />
                            <span class="text-[16.2px] font-medium">Developer</span>
                        </span>
                    </label>
                </div>
                @error('account_type')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="name" class="mb-2 block text-[16.2px] font-medium">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="input" placeholder="Budi Santoso" autocomplete="name">
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="email" class="mb-2 block text-[16.2px] font-medium">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" placeholder="budi@example.com" autocomplete="email">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-5">
                <label for="password" class="mb-2 block text-[16.2px] font-medium">Password</label>
                <input id="password" type="password" name="password" class="input" placeholder="••••••••" autocomplete="new-password">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mb-6">
                <label for="password_confirmation" class="mb-2 block text-[16.2px] font-medium">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" placeholder="••••••••" autocomplete="new-password">
                @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            {{-- Developer studio fields (hidden unless Developer is selected) --}}
            <div id="studio-fields" class="{{ old('account_type') === 'developer' ? '' : 'hidden' }} mb-6 space-y-5">
                <div>
                    <label for="studio_name" class="mb-2 block text-[16.2px] font-medium">Studio name</label>
                    <input id="studio_name" type="text" name="studio_name" value="{{ old('studio_name') }}" class="input" placeholder="Studio Nusantara" autocomplete="organization">
                    @error('studio_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="portfolio" class="mb-2 block text-[16.2px] font-medium">Portfolio <span class="font-normal text-subtle">(optional)</span></label>
                    <textarea id="portfolio" name="portfolio" rows="3" class="input resize-none" placeholder="Link to your games or website…">{{ old('portfolio') }}</textarea>
                    @error('portfolio')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mb-6 flex items-start gap-3">
                <input id="terms" type="checkbox" name="terms" value="on" class="mt-1 h-4 w-4 rounded border-border-strong bg-elevated accent-[#1D4ED8] {{ $errors->has('terms') ? 'border-danger' : '' }}" {{ old('terms') ? 'checked' : '' }}>
                <label for="terms" class="text-[16.2px] text-subtle">I agree to the <a href="{{ route('home') }}" class="text-link hover:underline">Terms of Service</a> and <a href="{{ route('home') }}" class="text-link hover:underline">Privacy Policy</a></label>
            </div>
            @error('terms')<p class="field-error">{{ $message }}</p>@enderror

            <button type="submit" class="btn-primary w-full">Create Account</button>
        </form>

        <p class="mt-6 text-center text-[16.2px] text-subtle">Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-link hover:underline">Sign in</a>
        </p>
    </div>

    <script>
        (function () {
            var studio = document.getElementById('studio-fields');
            var sync = function () {
                var selected = document.querySelector('input[name="account_type"]:checked');
                studio.classList.toggle('hidden', !selected || selected.value !== 'developer');
            };
            document.querySelectorAll('input[name="account_type"]').forEach(function (radio) {
                radio.addEventListener('change', sync);
            });
            sync();
        })();
    </script>

    <x-toasts />
</body>
</html>
