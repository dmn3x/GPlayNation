<x-portal-layout title="Upload game" section="developer">
    <h1 class="screen-title">Upload game</h1>
    <p class="mt-1 text-subtle">Your game will be reviewed before it appears in the store.</p>

    <form method="POST" action="{{ route('dev.games.store') }}" enctype="multipart/form-data">
        @csrf
        @include('developer.games._form')
        <div class="mt-4 flex gap-3">
            <button class="btn-primary">Submit for review</button>
            <a href="{{ route('dev.games.index') }}" class="btn-ghost">Cancel</a>
        </div>
    </form>
</x-portal-layout>
