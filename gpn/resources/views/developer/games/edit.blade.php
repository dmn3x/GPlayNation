<x-portal-layout title="Edit game" section="developer">
    <h1 class="screen-title">Edit — {{ $game->name }}</h1>
    <p class="mt-1 text-subtle">Changing the title, description, genre, or files sends the game back for review. Price changes apply instantly.</p>

    <form method="POST" action="{{ route('dev.games.update', $game) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('developer.games._form')
        <div class="mt-4 flex gap-3">
            <button class="btn-primary">Save changes</button>
            <a href="{{ route('dev.games.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>

    <section class="mt-10 max-w-2xl">
        <h2 class="section-heading">In-app purchases</h2>
        <p class="mt-1 text-subtle">Edit prices and availability without sending the game back for review.</p>

        @forelse ($game->iaps as $iap)
            <form method="POST" action="{{ route('dev.iaps.update', $iap) }}" class="card mt-4 space-y-4 p-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="iap-name-{{ $iap->id }}" class="mb-1 block text-sm font-medium">Name</label>
                        <input type="text" id="iap-name-{{ $iap->id }}" name="name" value="{{ old('name', $iap->name) }}" required maxlength="150" class="input">
                    </div>
                    <div>
                        <label for="iap-price-{{ $iap->id }}" class="mb-1 block text-sm font-medium">Price (IDR)</label>
                        <input type="number" id="iap-price-{{ $iap->id }}" name="price" value="{{ old('price', $iap->price) }}" required min="0" step="0.01" class="input">
                    </div>
                </div>

                <div>
                    <label for="iap-type-{{ $iap->id }}" class="mb-1 block text-sm font-medium">Type</label>
                    <select id="iap-type-{{ $iap->id }}" name="type" required class="input">
                        @foreach (['currency', 'item', 'upgrade', 'cosmetic'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $iap->type->value) === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="iap-description-{{ $iap->id }}" class="mb-1 block text-sm font-medium">Description <span class="text-faint">(optional)</span></label>
                    <textarea id="iap-description-{{ $iap->id }}" name="description" rows="2" maxlength="1000" class="input">{{ old('description', $iap->description) }}</textarea>
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="iap-active-{{ $iap->id }}" name="is_active" value="1" @checked(old('is_active', $iap->is_active)) class="h-4 w-4 rounded border-border-strong bg-elevated accent-[#1D4ED8]">
                    <label for="iap-active-{{ $iap->id }}" class="text-[16.2px] text-subtle">Active in store</label>
                </div>

                <div>
                    <button class="btn-primary">Save IAP</button>
                </div>
            </form>
        @empty
            <p class="card mt-4 p-6 text-[16.2px] text-subtle">No in-app purchases yet.</p>
        @endforelse
    </section>
</x-portal-layout>
