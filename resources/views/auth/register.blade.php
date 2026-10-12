<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0F0F10] font-sans antialiased">
    <x-auth-shell>
        <div class="flex flex-col items-center pb-[8.75px]">
            <div class="pb-[17.5px]">
                <a href="{{ route('home') }}" aria-label="GPlayNation home">
                    <x-logo class="h-[61px] w-auto text-white" />
                </a>
            </div>
            <h1 class="text-[21.87px] font-semibold leading-[30.62px] tracking-[-0.55px] text-white">Create Account</h1>
            <p class="text-[15.31px] leading-[21.87px] text-white/50">Join GPlayNation today</p>
        </div>

        <form id="register-form" method="POST" action="{{ route('register') }}" class="flex flex-col gap-[17.5px] py-[26px]">
            @csrf

            <div role="radiogroup" aria-label="Account type">
                <p class="mb-[6px] pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Account type</p>
                <div class="grid grid-cols-2 gap-[13px]">
                    <label>
                        <input type="radio" name="account_type" value="user" class="peer sr-only" {{ old('account_type', 'user') === 'user' ? 'checked' : '' }}>
                        <span class="flex h-[46px] cursor-pointer items-center justify-center gap-2 rounded-[17.5px] border border-[#222224] bg-[#0F0F10] text-[15.31px] font-medium text-white transition peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]/10 peer-focus-visible:shadow-[0_0_0_1.67px_#1D4ED8]">
                            <x-icon name="gamepad" class="h-[17.5px] w-[17.5px]" />
                            <span>Gamer</span>
                        </span>
                    </label>
                    <label>
                        <input type="radio" name="account_type" value="developer" class="peer sr-only" {{ old('account_type', 'user') === 'developer' ? 'checked' : '' }}>
                        <span class="flex h-[46px] cursor-pointer items-center justify-center gap-2 rounded-[17.5px] border border-[#222224] bg-[#0F0F10] text-[15.31px] font-medium text-white transition peer-checked:border-[#1D4ED8] peer-checked:bg-[#1D4ED8]/10 peer-focus-visible:shadow-[0_0_0_1.67px_#1D4ED8]">
                            <x-icon name="cog" class="h-[17.5px] w-[17.5px]" />
                            <span>Developer</span>
                        </span>
                    </label>
                </div>
                @error('account_type')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="name" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Full Name</label>
                <div class="relative">
                    <x-icon name="user" class="absolute top-1/2 left-[13px] h-[17.5px] w-[17.5px] -translate-y-1/2 text-white/40" />
                    <input id="name" type="text" name="name" value="{{ old('name') }}" class="input-auth" placeholder="John Doe" autocomplete="name">
                </div>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Email</label>
                <div class="relative">
                    <x-icon name="mail" class="absolute top-1/2 left-[13px] h-[17.5px] w-[17.5px] -translate-y-1/2 text-white/40" />
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="input-auth" placeholder="name@example.com" autocomplete="email">
                </div>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Password</label>
                <div class="relative">
                    <x-icon name="lock" class="absolute top-1/2 left-[13px] h-[17.5px] w-[17.5px] -translate-y-1/2 text-white/40" />
                    <input id="password" type="password" name="password" class="input-auth" placeholder="••••••••" autocomplete="new-password">
                </div>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Confirm Password</label>
                <div class="relative">
                    <x-icon name="lock" class="absolute top-1/2 left-[13px] h-[17.5px] w-[17.5px] -translate-y-1/2 text-white/40" />
                    <input id="password_confirmation" type="password" name="password_confirmation" class="input-auth" placeholder="••••••••" autocomplete="new-password">
                </div>
            </div>

            <div id="studio-fields" class="{{ old('account_type') === 'developer' ? '' : 'hidden' }} flex flex-col gap-[17.5px]">
                <div>
                    <label for="studio_name" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Studio name</label>
                    <input id="studio_name" type="text" name="studio_name" value="{{ old('studio_name') }}" class="input-auth !pl-[18.6px]" placeholder="Studio Nusantara" autocomplete="organization">
                    @error('studio_name')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="portfolio" class="mb-[6px] block pl-[4.37px] text-[13.12px] font-medium leading-[19.68px] text-white/70">Portfolio <span class="font-normal text-white/40">(optional)</span></label>
                    <textarea id="portfolio" name="portfolio" rows="3" class="input-auth h-auto resize-none !pl-[18.6px] py-3" placeholder="Link to your games or website…">{{ old('portfolio') }}</textarea>
                    @error('portfolio')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-start gap-[8.75px] pb-[17.5px]">
                <input id="terms" type="checkbox" name="terms" value="on" class="mt-[2px] h-[14.2px] w-[14.2px] shrink-0 rounded border-[#222224] bg-[#0F0F10] accent-[#1D4ED8]" {{ old('terms') ? 'checked' : '' }}>
                <label for="terms" class="text-[13.12px] font-medium leading-[17.5px] text-white/70">I agree to the <a href="{{ route('home') }}" class="text-[#1D4ED8] hover:underline">Terms of Service</a> and <a href="{{ route('home') }}" class="text-[#1D4ED8] hover:underline">Privacy Policy</a></label>
            </div>
            @error('terms')<p class="field-error">{{ $message }}</p>@enderror

            <button type="submit" class="btn-auth">Create Account</button>
        </form>

        <div class="flex items-center py-[17.5px]" aria-hidden="true">
            <span class="h-px w-[105px] bg-[#222224]"></span>
            <span class="w-[136px] text-center text-[13.12px] text-white/40">or continue with</span>
            <span class="h-px w-[105px] bg-[#222224]"></span>
        </div>

        <div class="flex gap-[13px]">
            <button type="button" data-social="Google" class="social-btn flex-1">
                <svg class="h-[17.5px] w-[17.5px] fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>
                <span>Google</span>
            </button>
            <button type="button" data-social="Apple" class="social-btn flex-1">
                <svg class="h-[17.5px] w-[17.5px] fill-white" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zM15.53 3.83c.843-1.012 1.4-2.427 1.245-3.83-1.207.052-2.662.805-3.532 1.818-.78.896-1.454 2.338-1.273 3.714 1.338.104 2.715-.688 3.559-1.701"/></svg>
                <span>Apple</span>
            </button>
        </div>

        <p class="flex justify-center pt-[21.87px] text-[14.22px] leading-[21.32px] text-white/50">Already have an account?&nbsp;<a href="{{ route('login') }}" class="text-[#1D4ED8] hover:underline">Sign in</a></p>
    </x-auth-shell>

    <div id="social-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
        <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[15.31px] text-white shadow-lg"><span id="social-soon-name">Social</span> sign-in is coming soon.</p>
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

    <x-toasts />
</body>
</html>
