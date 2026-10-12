@props(['library'])
@php $game = $library->game; @endphp
@if ($game)
    <div class="card flex flex-col overflow-hidden">
        <a href="{{ route('store.show', $game->slug) }}" class="block aspect-video overflow-hidden bg-elevated">
            <img src="{{ asset('storage/'.$game->cover_path) }}" alt="{{ $game->name }} cover" loading="lazy" class="h-full w-full object-cover transition duration-300 hover:scale-[1.02]">
        </a>
        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="min-w-0">
                <p class="truncate font-semibold" title="{{ $game->name }}">{{ $game->name }}</p>
                <p class="truncate text-sm text-faint">{{ $game->genre }}</p>
            </div>
            <form method="POST" action="{{ route('profile.showcase', $library) }}" class="mt-auto">
                @csrf
                <button class="btn-ghost w-full !py-2 !text-sm">
                    <x-icon :name="$library->is_showcased ? 'x' : 'star'" class="h-4 w-4" />
                    {{ $library->is_showcased ? 'Remove from showcase' : 'Add to showcase' }}
                </button>
            </form>
        </div>
    </div>
@endif
