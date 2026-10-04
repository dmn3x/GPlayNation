@extends('layouts.app')
@section('title', 'Sign in with '.ucfirst($provider).' — GPlayNation')

@section('content')
<div class="grid min-h-[calc(100dvh-48px)] place-items-center px-6 py-16">
    <div class="gpn-panel w-full max-w-[420px] p-9 text-center">
        <x-icon :name="$provider" class="mx-auto h-10 w-10" />
        <h1 class="mt-5 text-2xl font-semibold">Continue with {{ ucfirst($provider) }}</h1>
        <p class="mt-2 text-sm text-gpn-fg-faint">
            Provider credentials are not configured in this environment. The OAuth
            route contract is in place; connecting a real client only requires
            the token exchange in <code>OAuthController@callback</code>.
        </p>

        <a href="{{ route('login') }}" class="gpn-btn gpn-btn-ghost mt-7 w-full">Back to login</a>
    </div>
</div>
@endsection
