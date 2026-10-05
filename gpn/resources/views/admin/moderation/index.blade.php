<x-portal-layout title="Moderation" section="admin">
    <h1 class="screen-title">Game moderation</h1>
    <p class="mt-1 text-subtle">Review games awaiting publication.</p>

    <div class="card mt-6 overflow-hidden">
        @if ($games->isEmpty())
            <p class="p-5 text-subtle">No games awaiting review.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Game</th>
                        <th class="px-4 py-3 font-medium">Studio</th>
                        <th class="px-4 py-3 font-medium">Submitted</th>
                        <th class="px-4 py-3 font-medium">Genre</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($games as $game)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $game->name }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $game->developer?->studio_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $game->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $game->genre }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($game->price) }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.moderation.show', $game) }}" class="btn-ghost px-3 py-1.5 text-sm">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $games->links() }}</div>
</x-portal-layout>
