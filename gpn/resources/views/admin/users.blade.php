@extends('admin.layout')
@section('title', 'Verifikasi Akun — Admin')

@section('admin')
<h1 class="text-3xl font-semibold">Verifikasi Akun</h1>
<p class="mt-1 text-gpn-fg-muted">SRS 3.1.1.4 — approve or reject developer registrations.</p>

@if ($users->isEmpty())
    <x-empty-state class="mt-8" icon="users" title="Tidak ada akun Developer" />
@else
    <ul class="mt-8 space-y-3">
        @foreach ($users as $user)
            <li class="gpn-panel px-5 py-4">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $user->developer?->nama_developer ?? $user->pengguna_nama }}</p>
                        <p class="text-sm text-gpn-fg-faint">{{ $user->email }} · {{ $user->developer?->nama_studio }}</p>
                    </div>

                    <span class="gpn-tag ml-auto {{ $user->status === 'active' ? '!border-emerald-500/40 !text-emerald-300' : ($user->status === 'rejected' ? '!border-red-500/40 !text-red-300' : '') }}">
                        {{ $user->status }}
                    </span>

                    @if ($user->status === 'pending')
                        <form method="POST" action="{{ route('admin.users.verify', $user) }}" class="flex items-center gap-2">
                            @csrf
                            <button name="aksi" value="setujui" class="gpn-btn gpn-btn-primary gpn-btn-sm">
                                <x-icon name="check" class="h-4 w-4" /> Setujui
                            </button>
                            <button name="aksi" value="tolak" class="gpn-btn gpn-btn-ghost gpn-btn-sm">
                                Tolak
                            </button>
                        </form>
                    @endif
                </div>

                @if ($user->status === 'pending')
                    {{-- 3.1.1.4 alternate flow: rejection requires a reason --}}
                    <form method="POST" action="{{ route('admin.users.verify', $user) }}"
                          class="mt-3 flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="aksi" value="tolak">
                        <label for="alasan-{{ $user->id }}" class="sr-only">Alasan penolakan</label>
                        <input id="alasan-{{ $user->id }}" name="alasan" placeholder="Alasan penolakan (untuk tolak)"
                               class="gpn-input py-2 text-sm">
                        <button class="gpn-btn gpn-btn-quiet gpn-btn-sm">Kirim</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>

    <div class="mt-8">{{ $users->links() }}</div>
@endif
@endsection
