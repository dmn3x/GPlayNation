<x-portal-layout title="Settings" section="developer">
    <h1 class="text-2xl font-bold tracking-tight text-white">Settings</h1>
    <p class="mt-1 text-sm text-white/50">Manage your studio profile and payout details.</p>

    <form method="POST" action="{{ route('dev.settings.update') }}" class="card mt-6 max-w-2xl space-y-4 p-6">
        @csrf
        <div>
            <label for="studio_name" class="mb-1 block text-sm font-medium">Studio name</label>
            <input type="text" id="studio_name" name="studio_name" value="{{ old('studio_name', $developer->studio_name) }}" required maxlength="120" class="input">
            @error('studio_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="portfolio" class="mb-1 block text-sm font-medium">Portfolio <span class="text-faint">(optional)</span></label>
            <input type="text" id="portfolio" name="portfolio" value="{{ old('portfolio', $developer->portfolio) }}" maxlength="2000" class="input">
            @error('portfolio')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="payout_bank" class="mb-1 block text-sm font-medium">Payout bank</label>
                <input type="text" id="payout_bank" name="payout_bank" value="{{ old('payout_bank', $developer->payout_bank) }}" maxlength="100" class="input">
                @error('payout_bank')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="payout_account" class="mb-1 block text-sm font-medium">Payout account</label>
                <input type="text" id="payout_account" name="payout_account" value="{{ old('payout_account', $developer->payout_account) }}" maxlength="100" class="input">
                @error('payout_account')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <button class="btn-primary">Save settings</button>
    </form>
</x-portal-layout>
