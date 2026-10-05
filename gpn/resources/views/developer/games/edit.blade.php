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
</x-portal-layout>
