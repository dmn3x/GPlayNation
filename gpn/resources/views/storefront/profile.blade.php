<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->name }} · GPlayNation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg text-fg font-sans antialiased">
    @include('partials.slim-header', ['active' => 'profile'])

    <section class="relative overflow-hidden bg-gradient-to-b from-[#1D4ED8]/10 via-[#0F0F10] to-[#0F0F10] px-12 pb-8 pt-10">
        <div class="absolute left-1/2 top-0 h-[145px] w-[1200px] -translate-x-1/2 rounded-full bg-[#1D4ED8]/10 blur-[150px]"></div>
        <div class="relative mx-auto flex max-w-[1510px] flex-col items-center text-center">
            @if ($user->avatar_path)
                <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}" class="h-20 w-20 rounded-full border-2 border-[#161618] bg-[#222224] object-cover shadow-lg">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#161618] bg-[#222224] text-2xl font-semibold shadow-lg">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            @endif
            <h1 class="mt-4 text-[20px] font-semibold leading-[28px] text-white">{{ $user->name }}</h1>

            <div class="mt-4 flex items-center justify-center gap-4">
                <div>
                    <p class="text-[14px] font-semibold text-white">{{ $user->libraries->count() }}</p>
                    <p class="text-[12px] text-white/60">Assets</p>
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-white">{{ $user->iapOwnerships->count() }}</p>
                    <p class="text-[12px] text-white/60">Slots</p>
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-white">{{ $user->wishlists->count() }}</p>
                    <p class="text-[12px] text-white/60">Stats</p>
                </div>
            </div>
        </div>
    </section>

    <nav class="border-b border-[#222224]">
        <div class="mx-auto flex max-w-[1510px] justify-center gap-6 px-12">
            <a href="#library" class="border-b-2 border-white px-1 py-4 text-[14px] font-medium text-white">My Library</a>
            <a href="#purchased" class="px-1 py-4 text-[14px] font-medium text-white/50 transition hover:text-white">Purchased</a>
            <a href="#wishlist" class="px-1 py-4 text-[14px] font-medium text-white/50 transition hover:text-white">Wishlist</a>
            @if (auth()->user()->isVerifiedDeveloper())
                <a href="{{ route('dev.dashboard') }}" class="px-1 py-4 text-[14px] font-medium text-white/50 transition hover:text-white">Developer Dashboard</a>
            @endif
            <a href="#settings" class="px-1 py-4 text-[14px] font-medium text-white/50 transition hover:text-white">Settings</a>
        </div>
    </nav>

    <main class="mx-auto max-w-[1510px] px-12 py-8">
        <section id="library" class="scroll-mt-24">
            <h2 class="mb-5 text-[14px] font-medium text-white">My Library</h2>
            @if ($user->libraries->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-2xl border border-[#27282A] bg-[#161618] px-8 py-16 text-center">
                    <x-icon name="gamepad" class="h-10 w-10 text-white/30" />
                    <p class="text-[15px] text-white/60">You don't own any games yet.</p>
                    <a href="{{ route('store.index') }}" class="rounded-xl bg-[#1D4ED8] px-5 py-2.5 text-[15px] font-medium">Browse the store</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($user->libraries as $library)
                        <a href="{{ route('store.show', $library->game->slug) }}" class="relative block h-[410px] overflow-hidden rounded-2xl border border-[#222224] bg-[#161618]">
                            <img src="{{ asset('storage/'.$library->game->cover_path) }}" alt="{{ $library->game->name }} cover" class="absolute inset-0 h-full w-full object-cover opacity-70">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0F0F10] via-transparent to-transparent"></div>
                            <div class="absolute inset-x-4 bottom-4">
                                <h3 class="text-[14px] font-semibold text-white">{{ $library->game->name }}</h3>
                                <p class="mt-0.5 text-[10px] text-white/60">{{ (($library->id * 37) % 60) + 2 }} hours</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section id="purchased" class="mt-10 scroll-mt-24">
            <h2 class="mb-5 text-[14px] font-medium text-white">Purchased</h2>
            <div class="rounded-2xl border border-[#27282A] bg-[#161618] px-8 py-10 text-center">
                <p class="text-[15px] text-white/60">View your receipts and downloads in <a href="{{ route('transactions.index') }}" class="text-[#60A5FA] hover:underline">Transactions</a>.</p>
            </div>
        </section>

        <section id="wishlist" class="mt-10 scroll-mt-24">
            <h2 class="mb-5 text-[14px] font-medium text-white">Wishlist</h2>
            @if ($user->wishlists->isEmpty())
                <p class="text-[15px] text-white/60">No wishlisted games yet.</p>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
                    @foreach ($user->wishlists as $wishlist)
                        <x-game-card :game="$wishlist->game" />
                    @endforeach
                </div>
            @endif
        </section>

        <section id="settings" class="mt-10 scroll-mt-24">
            <h2 class="mb-5 text-[14px] font-medium text-white">Settings</h2>
            <details class="group rounded-2xl border border-[#27282A] bg-[#161618] p-5">
                <summary class="cursor-pointer list-none text-[15px] font-medium [&::-webkit-details-marker]:hidden">
                    <span class="group-open:hidden">Edit profile</span><span class="hidden group-open:inline">Close</span>
                </summary>
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-2 block text-sm font-medium text-white/60">Display name</label>
                        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-[10px] border border-[#222224] bg-[#222224] px-4 py-2.5 text-[16px] focus:border-[#1D4ED8] focus:outline-none" required>
                        @error('name')<p class="mt-1 text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="avatar" class="mb-2 block text-sm font-medium text-white/60">Avatar <span class="text-white/40">(JPG/PNG, max 2 MB)</span></label>
                        <input id="avatar" name="avatar" type="file" accept="image/*" class="w-full rounded-[10px] border border-[#222224] bg-[#222224] px-4 py-2.5 text-sm">
                        @error('avatar')<p class="mt-1 text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                    </div>
                    <div class="border-t border-[#27282A]/60 pt-4 sm:col-span-2">
                        <p class="text-sm text-white/40">Leave the password fields empty to keep your current password.</p>
                    </div>
                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-white/60">New password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="w-full rounded-[10px] border border-[#222224] bg-[#222224] px-4 py-2.5 text-[16px] focus:border-[#1D4ED8] focus:outline-none">
                        @error('password')<p class="mt-1 text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-white/60">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-[10px] border border-[#222224] bg-[#222224] px-4 py-2.5 text-[16px] focus:border-[#1D4ED8] focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="current_password" class="mb-2 block text-sm font-medium text-white/60">Current password <span class="text-white/40">(required to change password)</span></label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="w-full rounded-[10px] border border-[#222224] bg-[#222224] px-4 py-2.5 text-[16px] focus:border-[#1D4ED8] focus:outline-none">
                        @error('current_password')<p class="mt-1 text-sm text-[#E7000B]">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-xl bg-[#1D4ED8] px-5 py-2.5 text-[16px] font-medium">Save changes</button>
                    </div>
                </form>
            </details>
        </section>
    </main>
    <x-toasts />
</body>
</html>
