<x-app-layout :title="$user->name">
    <div class="space-y-8">
        {{-- Profile header --}}
        <section class="card overflow-hidden">
            <div class="h-32 bg-gradient-to-br from-accent/25 via-accent/5 to-transparent"></div>
            <div class="px-6 pb-6">
                <div class="-mt-10 flex flex-col gap-4 sm:flex-row sm:items-end">
                    @if ($user->avatar_path)
                        <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="{{ $user->name }}" class="h-20 w-20 shrink-0 rounded-full border-2 border-bg object-cover shadow-card">
                    @else
                        <span class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full border-2 border-bg bg-accent text-2xl font-semibold shadow-card">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <h1 class="screen-title truncate">{{ $user->name }}</h1>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="rounded-full bg-accent/15 px-2.5 py-0.5 text-sm font-medium text-link">{{ ucfirst($user->role->value) }}</span>
                            <span class="truncate text-sm text-faint">{{ $user->email }}</span>
                        </div>
                    </div>

                    <a href="{{ route('transactions.index') }}" class="btn-ghost !py-2">
                        <x-icon name="wallet" class="h-4 w-4" /> Transaction history
                    </a>
                </div>

                {{-- Stats --}}
                <div class="mt-6 grid grid-cols-3 gap-4">
                    <div class="card p-4">
                        <p class="text-[23px] font-semibold">{{ $user->libraries->count() }}</p>
                        <p class="text-sm text-muted">Games owned</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-[23px] font-semibold">{{ $user->wishlists->count() }}</p>
                        <p class="text-sm text-muted">Wishlist</p>
                    </div>
                    <div class="card p-4">
                        <p class="text-[23px] font-semibold">{{ $user->iapOwnerships->count() }}</p>
                        <p class="text-sm text-muted">IAP items</p>
                    </div>
                </div>

                {{-- Edit form toggle --}}
                <details class="group mt-6">
                    <summary class="btn-ghost inline-flex cursor-pointer list-none">
                        <x-icon name="pencil" class="h-4 w-4" /> <span class="group-open:hidden">Edit profile</span><span class="hidden group-open:inline">Close</span>
                    </summary>
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-2">
                        @csrf
                        @method('PUT')

                        <div class="sm:col-span-2">
                            <label for="name" class="mb-2 block text-sm font-medium text-muted">Display name</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required>
                            @error('name')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="avatar" class="mb-2 block text-sm font-medium text-muted">Avatar <span class="text-faint">(JPG/PNG, max 2 MB)</span></label>
                            <input id="avatar" name="avatar" type="file" accept="image/*" class="input !py-2">
                            @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2 border-t border-border-strong/60 pt-4">
                            <p class="text-sm text-faint">Leave the password fields empty to keep your current password.</p>
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-muted">New password</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" class="input">
                            @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-muted">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="current_password" class="mb-2 block text-sm font-medium text-muted">Current password <span class="text-faint">(required to change password)</span></label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="input">
                            @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-primary">Save changes</button>
                        </div>
                    </form>
                </details>
            </div>
        </section>

        {{-- Showcase --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <h2 class="section-heading">Showcase</h2>
                <span class="text-sm text-faint">Games featured on your profile</span>
            </div>
            @if ($showcased->isEmpty())
                <div class="card px-6 py-10 text-center text-subtle">
                    Add games from your library to feature them on your profile.
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($showcased as $library)
                        <x-library-card :library="$library" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Full library --}}
        <section class="space-y-4">
            <h2 class="section-heading">Your library</h2>
            @if ($user->libraries->isEmpty())
                <div class="card flex flex-col items-center gap-3 px-8 py-12 text-center">
                    <x-icon name="gamepad" class="h-10 w-10 text-faint" />
                    <p class="text-[16.2px] text-subtle">You don't own any games yet.</p>
                    <a href="{{ route('store.index') }}" class="btn-primary">Browse the store</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($user->libraries as $library)
                        <x-library-card :library="$library" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Wishlist --}}
        <section class="space-y-4">
            <h2 class="section-heading">Wishlist</h2>
            @if ($user->wishlists->isEmpty())
                <p class="text-[16.2px] text-subtle">No wishlisted games yet.</p>
            @else
                <div class="flex gap-4 overflow-x-auto pb-2">
                    @foreach ($user->wishlists as $wishlist)
                        <div class="w-56 shrink-0">
                            <x-game-card :game="$wishlist->game" />
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
