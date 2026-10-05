<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-bg px-6 py-12">
    <div class="card flex w-full max-w-[440px] flex-col items-center gap-3 px-8 py-10 text-center">
        <p class="text-[64px] font-semibold leading-none tracking-[-1.6px] text-faint">500</p>
        <h1 class="section-heading">Something went wrong</h1>
        <p class="text-[16.2px] text-subtle">An unexpected error occurred. Please try again later.</p>
        <a href="{{ route('home') }}" class="btn-primary mt-2">Back to GPlayNation</a>
    </div>
</body>
</html>
