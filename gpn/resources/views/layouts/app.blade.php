<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GPlayNation')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
<div class="mx-auto flex min-h-dvh max-w-[1512px] flex-col">

    @auth
        <x-app-nav />
    @else
        <x-guest-nav />
    @endauth

    <main class="flex-1">
        <x-anim-overlay />
        @if (session('status'))
            <div class="mx-8 mt-6 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mx-8 mt-6 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-300">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</div>
</body>
</html>