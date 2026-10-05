<x-portal-layout title="Promotion" section="developer">
    <h1 class="screen-title">Promotion</h1>
    <p class="mt-1 text-subtle">Create time-limited discounts for your games.</p>

    <div class="card mt-6 p-5">
        <h2 class="section-heading">New promotion</h2>
        <form method="POST" action="{{ route('dev.promotion.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="flex flex-col gap-1 text-sm text-subtle">Game
                <select name="game_id" required class="input w-auto">
                    @foreach ($games as $game)
                        <option value="{{ $game->id }}" @selected((string) old('game_id') === (string) $game->id)>{{ $game->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1 text-sm text-subtle">Discount %
                <input type="number" name="discount_percent" required min="1" max="90" value="{{ old('discount_percent') }}" class="input w-28">
            </label>
            <label class="flex flex-col gap-1 text-sm text-subtle">Starts at
                <input type="datetime-local" name="starts_at" required value="{{ old('starts_at') }}" class="input w-auto">
            </label>
            <label class="flex flex-col gap-1 text-sm text-subtle">Ends at
                <input type="datetime-local" name="ends_at" required value="{{ old('ends_at') }}" class="input w-auto">
            </label>
            <button class="btn-primary">Create</button>
        </form>
        @error('game_id')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        @error('discount_percent')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        @error('starts_at')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
        @error('ends_at')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <h2 class="section-heading mt-6">Promotions</h2>
    <div class="card mt-3 overflow-hidden">
        @if ($promotions->isEmpty())
            <p class="p-5 text-subtle">No promotions yet.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Game</th>
                        <th class="px-4 py-3 font-medium">Discount</th>
                        <th class="px-4 py-3 font-medium">Period</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($promotions as $promotion)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3">{{ $promotion->game->name }}</td>
                            <td class="px-4 py-3">{{ $promotion->discount_percent }}%</td>
                            <td class="px-4 py-3 text-subtle">{{ $promotion->starts_at->format('d M Y H:i') }} – {{ $promotion->ends_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium',
                                    'bg-success/15 text-success' => $promotion->status_label === 'active',
                                    'bg-elevated text-subtle' => $promotion->status_label === 'scheduled',
                                    'bg-elevated text-faint opacity-60' => $promotion->status_label === 'ended',
                                ])>{{ ucfirst($promotion->status_label) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($promotion->status_label === 'scheduled')
                                    <form method="POST" action="{{ route('dev.promotion.destroy', $promotion) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-ghost px-3 py-1.5 text-sm">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-portal-layout>
