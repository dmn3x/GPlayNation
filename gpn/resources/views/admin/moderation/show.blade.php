<x-portal-layout title="Review {{ $game->name }}" section="admin">
    <a href="{{ route('admin.moderation.index') }}" class="text-sm text-subtle hover:text-fg">&larr; Back to queue</a>
    <h1 class="screen-title mt-2">{{ $game->name }}</h1>
    <p class="mt-1 text-subtle">by {{ $game->developer?->studio_name ?? '—' }} · {{ $game->genre }} · {{ \App\Support\Money::format($game->price) }}</p>

    @if ($game->cover_path)
        <img src="{{ Storage::url($game->cover_path) }}" alt="" class="card mt-6 max-h-72 w-auto object-cover">
    @endif

    <div class="card mt-6 p-5">
        <h2 class="section-heading">Description</h2>
        <p class="mt-2 whitespace-pre-line text-subtle">{{ $game->description }}</p>
        @if ($game->trailer_url)
            <p class="mt-3 text-sm"><a href="{{ $game->trailer_url }}" target="_blank" rel="noopener" class="text-accent hover:underline">Watch trailer</a></p>
        @endif
        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
            <div><dt class="text-faint">Status</dt><dd class="mt-0.5 font-medium">{{ str_replace('_', ' ', $game->status->value) }}</dd></div>
            <div><dt class="text-faint">Submitted</dt><dd class="mt-0.5">{{ $game->created_at->format('d M Y H:i') }}</dd></div>
            <div><dt class="text-faint">Installer size</dt><dd class="mt-0.5">{{ $fileSize !== null ? number_format($fileSize / 1048576, 2) . ' MB' : '—' }}</dd></div>
            <div><dt class="text-faint">IAPs</dt><dd class="mt-0.5">{{ $game->iaps->count() }}</dd></div>
        </dl>
    </div>

    @if ($game->screenshots->isNotEmpty())
        <h2 class="section-heading mt-6">Screenshots</h2>
        <div class="mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($game->screenshots as $shot)
                <img src="{{ Storage::url($shot->path) }}" alt="" class="card aspect-video w-full object-cover">
            @endforeach
        </div>
    @endif

    @if ($game->iaps->isNotEmpty())
        <h2 class="section-heading mt-6">In-app purchases</h2>
        <div class="card mt-3 overflow-hidden">
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($game->iaps as $iap)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3">{{ $iap->name }}</td>
                            <td class="px-4 py-3 text-subtle">{{ ucfirst($iap->type->value) }}</td>
                            <td class="px-4 py-3">{{ \App\Support\Money::format($iap->price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card mt-6 flex flex-wrap items-start gap-3 p-5">
        <form method="POST" action="{{ route('admin.moderation.update', $game) }}">
            @csrf
            <input type="hidden" name="action" value="approve">
            <button class="btn-primary">Approve &amp; publish</button>
        </form>
        <form method="POST" action="{{ route('admin.moderation.update', $game) }}" class="flex flex-col gap-2">
            @csrf
            <input type="hidden" name="action" value="reject">
            <label class="flex flex-col gap-1 text-sm text-subtle">Rejection feedback
                <textarea name="feedback" rows="3" required maxlength="1000" placeholder="What must the developer fix?" class="input w-96">{{ old('feedback') }}</textarea>
            </label>
            @error('feedback')<p class="text-sm text-danger">{{ $message }}</p>@enderror
            <button class="btn-ghost w-fit px-4 py-2 text-sm text-danger">Reject with feedback</button>
        </form>
    </div>
</x-portal-layout>
