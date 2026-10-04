@extends('layouts.app')
@section('title', 'Login — GPlayNation')

@section('content')
<div class="grid min-h-[calc(100dvh-48px)] place-items-center px-6 py-16">
    <div class="gpn-panel w-full max-w-[420px] p-9">
        <div class="flex flex-col items-center gap-2">
            <x-logo class="h-9" />
            <h1 class="mt-3 text-3xl font-semibold">Login</h1>
            <p class="text-gpn-fg-faint">Welcome back to GPlayNation</p>
        </div>

        <form method="POST" action="{{ route('login') }}" class="mt-9 space-y-5">
            @csrf
            <div>
                <label for="email" class="gpn-label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                       autocomplete="email" autofocus
                       class="gpn-input @error('email') border-red-500 @enderror"
                       placeholder="name@example.com">
                @error('email')<p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="gpn-label">Password</label>
                <input id="password" name="password" type="password" required
                       autocomplete="current-password" class="gpn-input"
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
            </div>

            <label class="flex items-center gap-2 text-sm text-gpn-fg-faint">
                <input type="checkbox" name="remember" value="1"
                       class="rounded border-gpn-border-strong bg-gpn-bg">
                Remember me
            </label>

            <button type="submit" class="gpn-btn gpn-btn-primary w-full">Login</button>
        </form>

        {{-- SRS 2.6.3: Google / Apple OAuth — buttons wired in F-01/F-02 --}}
        <div class="my-6 flex items-center gap-4 text-sm text-gpn-fg-faint">
            <span class="h-px flex-1 bg-gpn-border-strong"></span>
            <span>or continue with</span>
            <span class="h-px flex-1 bg-gpn-border-strong"></span>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('oauth.redirect', 'google') }}" class="gpn-btn gpn-btn-ghost">
                <x-icon name="google" class="h-4 w-4" /> Google
            </a>
            <a href="{{ route('oauth.redirect', 'apple') }}" class="gpn-btn gpn-btn-ghost">
                <x-icon name="apple" class="h-4 w-4" /> Apple
            </a>
        </div>

        <p class="mt-8 text-center text-sm text-gpn-fg-muted">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-gpn-link hover:underline">Sign Up</a>
        </p>
    </div>
</div>
@endsection
