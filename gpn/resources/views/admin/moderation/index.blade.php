<x-portal-layout title="Moderation" section="admin">
    <div class="flex flex-wrap items-center justify-between gap-4 pb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Moderasi Game</h1>
            <p class="mt-1 text-sm text-white/50">Tinjau dan kelola pengajuan game dari developer.</p>
        </div>
        <button type="button" data-export-soon class="flex h-8 items-center gap-2 rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">
            <x-icon name="download" class="h-4 w-4" /> Ekspor Data
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#1D4ED8]/10 text-[#1D4ED8]"><x-icon name="users" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Menunggu Moderasi</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($stats['menunggu']) }}</span>
            </span>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#F6339A]/10 text-[#F6339A]"><x-icon name="heart" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Game Disetujui</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($stats['disetujui']) }}</span>
            </span>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-[#222224] bg-[#161618] p-5">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#F0B100]/10 text-[#F0B100]"><x-icon name="star" class="h-5 w-5" /></span>
            <span>
                <span class="block text-xs font-medium text-white/60">Game Ditolak</span>
                <span class="block text-2xl font-bold text-white">{{ number_format($stats['ditolak']) }}</span>
            </span>
        </div>
    </div>

    <section class="mt-4 rounded-2xl border border-[#222224] bg-[#161618] p-6">
        <h2 class="text-sm font-semibold text-white">Pengajuan Game</h2>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1">
                @foreach (['semua' => 'Semua', 'menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'] as $value => $label)
                    <a href="{{ route('admin.moderation.index', array_filter(['tab' => $value, 'q' => request('q')])) }}" @class(['flex h-[34px] items-center rounded-xl px-3 text-xs font-medium transition', 'border border-[#303034] bg-[#222224] text-white' => $tab === $value, 'text-white/60 hover:bg-white/5 hover:text-white' => $tab !== $value])>{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('admin.moderation.index') }}" class="flex h-[38px] w-64 items-center gap-2 rounded-xl border border-[#222224] bg-[#0F0F10] px-4">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <x-icon name="search" class="h-4 w-4 shrink-0 text-white/40" />
                <input name="q" value="{{ request('q') }}" placeholder="Cari game atau developer…" class="w-full bg-transparent text-xs text-white placeholder:text-white/50 focus:outline-none">
            </form>
        </div>

        @if ($games->isEmpty())
            <p class="py-10 text-center text-sm text-white/50">Tidak ada pengajuan pada tab ini.</p>
        @else
            <div class="overflow-x-auto">
            <table class="mt-2 w-full min-w-[640px] text-left">
                <thead>
                    <tr class="h-[33px] text-[10px] font-medium uppercase tracking-wide text-white/50">
                        <th class="font-medium">Game</th>
                        <th class="font-medium">Developer</th>
                        <th class="font-medium">Tanggal Pengajuan</th>
                        <th class="font-medium">Status</th>
                        <th class="font-medium"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($games as $game)
                        <tr class="h-[89px] border-t border-[#222224]">
                            <td class="py-4 pr-4">
                                <span class="flex items-center gap-3">
                                    @if ($game->cover_path)
                                        <img src="{{ Storage::url($game->cover_path) }}" alt="" aria-hidden="true" class="h-12 w-16 shrink-0 rounded-xl bg-[#222224] object-cover">
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block truncate text-xs text-[#E4E4E4]">{{ $game->name }}</span>
                                        <span class="block text-xs text-white/60">{{ $game->genre }}</span>
                                    </span>
                                </span>
                            </td>
                            <td class="py-4 pr-4 text-xs text-white/60">{{ $game->developer?->studio_name ?? '—' }}</td>
                            <td class="py-4 pr-4 text-xs text-white/60">{{ $game->created_at->format('d M Y') }}</td>
                            <td class="py-4 pr-4">
                                @if ($game->status === \App\Enums\GameStatus::PendingReview)
                                    <span class="rounded bg-[#F0B100]/10 px-2 py-1 text-[10px] font-medium text-[#EAB308]">Menunggu</span>
                                @elseif ($game->status === \App\Enums\GameStatus::Published)
                                    <span class="rounded bg-[#1D4ED8]/15 px-2 py-1 text-[10px] font-medium text-[#60A5FA]">Disetujui</span>
                                @else
                                    <span class="rounded bg-[#FF453A]/10 px-2 py-1 text-[10px] font-medium text-[#FF6467]">Ditolak</span>
                                @endif
                            </td>
                            <td class="py-4 text-right">
                                <a href="{{ route('admin.moderation.show', $game) }}" class="inline-flex h-8 items-center gap-1.5 rounded-xl border border-[#222224] px-3 text-xs font-medium text-white transition hover:bg-white/5">
                                    <x-icon name="pencil" class="h-3.5 w-3.5" /> {{ $game->status === \App\Enums\GameStatus::PendingReview ? 'Tinjau Game' : 'Lihat Detail' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#222224] pt-4 text-xs text-white/50">
            <span>Menampilkan {{ $games->firstItem() ?? 0 }}–{{ $games->lastItem() ?? 0 }} dari {{ $games->total() }} pengajuan</span>
            {{ $games->links() }}
        </div>
    </section>

    <div id="export-soon" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 hidden justify-center" role="status">
        <p class="rounded-xl border border-[#27282A] bg-[#161618] px-5 py-2.5 text-[14px] text-white shadow-lg">Ekspor data segera hadir.</p>
    </div>
    <script>
        (function () {
            var note = document.getElementById('export-soon');
            var timer = null;
            document.querySelectorAll('[data-export-soon]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    note.classList.remove('hidden');
                    note.classList.add('flex');
                    clearTimeout(timer);
                    timer = setTimeout(function () {
                        note.classList.add('hidden');
                        note.classList.remove('flex');
                    }, 2500);
                });
            });
        })();
    </script>
</x-portal-layout>
