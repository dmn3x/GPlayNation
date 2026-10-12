<x-portal-layout title="Commission" section="admin">
    <div class="flex flex-wrap items-center justify-between gap-4 pb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white">Kelola Skema Komisi</h1>
            <p class="mt-1 text-sm text-white/50">Atur pembagian komisi platform dan developer.</p>
        </div>
        <a href="#schedule-rate" class="flex h-10 items-center rounded-2xl bg-[#1D4ED8] px-4 text-sm font-medium text-white shadow-lg shadow-blue-800/40 transition hover:brightness-110">Tambah Skema</a>
    </div>

    <div class="card mt-6 max-w-md p-5">
        <p class="text-sm text-faint">Current rate</p>
        <p class="mt-1 text-4xl font-semibold">{{ number_format((float) $currentRate, 2) }}%</p>
    </div>

    <h2 id="schedule-rate" class="section-heading mt-6 scroll-mt-6">Schedule new rate</h2>
    <form method="POST" action="{{ route('admin.commission.store') }}" class="card mt-3 flex max-w-2xl flex-wrap items-end gap-3 p-5">
        @csrf
        <label class="flex flex-col gap-1 text-sm text-subtle">Percentage (%)
            <input type="number" name="percentage" value="{{ old('percentage') }}" required min="0" max="50" step="0.01" class="input w-40">
        </label>
        <label class="flex flex-col gap-1 text-sm text-subtle">Effective from
            <input type="date" name="effective_from" value="{{ old('effective_from') }}" required class="input w-auto">
        </label>
        <button class="btn-primary">Schedule</button>
    </form>
    @error('percentage')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror
    @error('effective_from')<p class="mt-2 text-sm text-danger">{{ $message }}</p>@enderror

    <h2 class="section-heading mt-6">Rate history</h2>
    <div class="card mt-3 overflow-hidden">
        @if ($schedule->isEmpty())
            <p class="p-5 text-subtle">No scheduled rates yet.</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Percentage</th>
                        <th class="px-4 py-3 font-medium">Effective from</th>
                        <th class="px-4 py-3 font-medium">Scheduled</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schedule as $row)
                        <tr class="border-b border-border last:border-0">
                            <td class="px-4 py-3">{{ number_format((float) $row->percentage, 2) }}%</td>
                            <td class="px-4 py-3 text-subtle">{{ $row->effective_from->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $row->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $schedule->links() }}</div>
</x-portal-layout>
