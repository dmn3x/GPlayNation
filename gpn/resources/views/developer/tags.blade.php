<x-portal-layout title="Tags" section="developer">
    <h1 class="screen-title">Tags</h1>
    <p class="mt-1 text-subtle">Organize your games with tags players can filter by.</p>

    <div class="card mt-6 p-5">
        <h2 class="section-heading">New tag</h2>
        <form method="POST" action="{{ route('dev.tags.store') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="flex flex-col gap-1 text-sm text-subtle">Name
                <input type="text" name="name" required maxlength="50" placeholder="pixel-art" value="{{ old('name') }}" class="input w-56">
            </label>
            <button class="btn-primary">Create</button>
        </form>
        @error('name')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
    </div>

    <h2 class="section-heading mt-6">Tags</h2>
    <div class="card mt-3 p-5">
        @if ($tags->isEmpty())
            <p class="text-subtle">No tags yet.</p>
        @else
            <ul class="flex flex-col gap-4">
                @foreach ($tags as $tag)
                    <li class="flex flex-wrap items-center gap-3 border-b border-border pb-4 last:border-0 last:pb-0">
                        <span class="inline-flex items-center rounded-full bg-elevated px-3 py-1 text-sm font-medium">{{ $tag->name }}</span>
                        <span class="text-sm text-faint">{{ $tag->games_count }} game(s)</span>
                        <form method="POST" action="{{ route('dev.tags.attach', $tag) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <select name="game_id" required class="input w-auto !py-1.5 text-sm">
                                @foreach ($games as $game)
                                    <option value="{{ $game->id }}">{{ $game->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn-ghost px-3 py-1.5 text-sm">Attach</button>
                        </form>
                        <form method="POST" action="{{ route('dev.tags.detach', $tag) }}" class="flex flex-wrap items-center gap-2">
                            @csrf
                            <select name="game_id" required class="input w-auto !py-1.5 text-sm">
                                @foreach ($games as $game)
                                    <option value="{{ $game->id }}">{{ $game->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn-ghost px-3 py-1.5 text-sm">Detach</button>
                        </form>
                        @if ($tag->games_count === 0)
                            <form method="POST" action="{{ route('dev.tags.destroy', $tag) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost px-3 py-1.5 text-sm">Delete</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-portal-layout>
