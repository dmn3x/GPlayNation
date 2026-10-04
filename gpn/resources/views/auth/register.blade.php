@extends('layouts.app')
@section('title', 'Sign Up — GPlayNation')

@section('content')
<div class="grid min-h-[calc(100dvh-48px)] place-items-center px-6 py-16">
    <div class="gpn-panel w-full max-w-[420px] p-9">
        <div class="flex flex-col items-center gap-2">
            <x-logo class="h-9" />
            <h1 class="mt-3 text-3xl font-semibold">Sign Up</h1>
            <p class="text-gpn-fg-faint">Bergabung dengan ekosistem game lokal</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="mt-9 space-y-5">
            @csrf

            {{-- SRS 3.1.1.1: choose account type before filling the form --}}
            <fieldset>
                <legend class="gpn-label">Account type</legend>
                <div class="grid grid-cols-2 gap-3">
                    <label class="gpn-btn gpn-btn-ghost w-full cursor-pointer has-[:checked]:border-gpn-accent has-[:checked]:bg-gpn-accent/10">
                        <input type="radio" name="role" value="user" class="sr-only"
                            @checked(old('role', 'user') !== 'developer')>
                        <x-icon name="user" class="h-4 w-4" /> Player
                    </label>
                    <label class="gpn-btn gpn-btn-ghost w-full cursor-pointer has-[:checked]:border-gpn-accent has-[:checked]:bg-gpn-accent/10">
                        <input type="radio" name="role" value="developer" class="sr-only"
                            @checked(old('role') === 'developer')>
                        <x-icon name="monitor" class="h-4 w-4" /> Developer
                    </label>
                </div>
                <p class="mt-2 text-sm text-gpn-fg-faint">
                    Developer akun menunggu verifikasi Admin sebelum dapat masuk.
                </p>
            </fieldset>

            <div>
                <label for="name" class="gpn-label">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                       autofocus class="gpn-input" placeholder="Your name">
                @error('name')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div id="studio-field" class="hidden">
                <label for="nama_studio" class="gpn-label">Studio name</label>
                <input id="nama_studio" name="nama_studio" type="text" value="{{ old('nama_studio') }}"
                       class="gpn-input" placeholder="Your studio">
                @error('nama_studio')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="email" class="gpn-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="email" class="gpn-input" placeholder="name@example.com">
                @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="gpn-label">Password</label>
                <input id="password" name="password" type="password" required
                       autocomplete="new-password" class="gpn-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                @error('password')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="gpn-label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       required autocomplete="new-password" class="gpn-input"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>

            <button type="submit" class="gpn-btn gpn-btn-primary w-full">Sign Up</button>
        </form>

        <p class="mt-8 text-center text-sm text-gpn-fg-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="text-gpn-link hover:underline">Login</a>
        </p>
    </div>
</div>

@push('scripts')
<script>
    // Reveal the studio field when the Developer account type is picked.
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const studioField = document.getElementById('studio-field');
    const studioInput = document.getElementById('nama_studio');

    function syncStudio() {
        const isDev = document.querySelector('input[name="role"]:checked')?.value === 'developer';
        studioField.classList.toggle('hidden', !isDev);
        studioInput.required = isDev;
    }

    roleInputs.forEach((i) => i.addEventListener('change', syncStudio));
    syncStudio();
</script>
@endpush
@endsection
