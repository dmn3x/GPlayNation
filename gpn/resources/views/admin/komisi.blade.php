@extends('admin.layout')
@section('title', 'Kelola Komisi — Admin')

@section('admin')
<h1 class="text-3xl font-semibold">Kelola Komisi</h1>
<p class="mt-1 text-gpn-fg-muted">SRS 3.1.4.2 / F-08 — platform commission schedule. Rates apply to new transactions only.</p>

<div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="gpn-panel overflow-hidden">
        <table class="w-full text-sm">
            <thead class="border-b border-gpn-border text-left text-gpn-fg-faint">
                <tr>
                    <th class="px-5 py-3">Berlaku mulai</th>
                    <th class="px-5 py-3">Persentase</th>
                    <th class="px-5 py-3">Ditetapkan oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gpn-border">
                @forelse ($komisi as $row)
                    <tr>
                        <td class="px-5 py-3">{{ $row->komisi_berlaku_mulai->format('j M Y') }}</td>
                        <td class="px-5 py-3 font-semibold">{{ number_format($row->komisi_persentase, 1) }}%</td>
                        <td class="px-5 py-3 text-gpn-fg-muted">{{ $row->admin?->pengguna_nama }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-gpn-fg-faint">Belum ada pengaturan komisi.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-5">{{ $komisi->links() }}</div>
    </div>

    <div class="gpn-panel p-6">
        <h2 class="font-medium">Add schedule</h2>
        <form method="POST" action="{{ route('admin.komisi.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="komisi_persentase" class="gpn-label">Percentage</label>
                <input id="komisi_persentase" name="komisi_persentase" type="number" step="0.01" min="0" max="100"
                       required class="gpn-input" value="{{ old('komisi_persentase', 20) }}">
                @error('komisi_persentase')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="komisi_berlaku_mulai" class="gpn-label">Effective from</label>
                <input id="komisi_berlaku_mulai" name="komisi_berlaku_mulai" type="date" required class="gpn-input">
                @error('komisi_berlaku_mulai')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
            </div>
            <button class="gpn-btn gpn-btn-primary w-full">Save</button>
        </form>
    </div>
</div>
@endsection
