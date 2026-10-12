<x-portal-layout title="Verification" section="admin">
    <div class="pb-8">
        <h1 class="text-2xl font-bold tracking-tight text-white">Verifikasi Developer</h1>
        <p class="mt-1 text-sm text-white/50">Setujui atau tolak pendaftaran developer.</p>
    </div>

    <div class="mt-6 flex gap-2">
        <a href="{{ route('admin.verification.index', ['tab' => 'pending']) }}" @class(['btn-ghost px-4 py-2 text-sm', 'btn-primary' => $tab !== 'rejected'])>Pending</a>
        <a href="{{ route('admin.verification.index', ['tab' => 'rejected']) }}" @class(['btn-ghost px-4 py-2 text-sm', 'btn-primary' => $tab === 'rejected'])>Rejected</a>
    </div>

    <div class="card mt-4 overflow-hidden">
        @if ($developers->isEmpty())
            <p class="p-5 text-subtle">{{ $tab === 'rejected' ? 'No rejected developers.' : 'No pending developers.' }}</p>
        @else
            <table class="w-full text-left text-[15px]">
                <thead>
                    <tr class="border-b border-border text-sm text-faint">
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Studio</th>
                        <th class="px-4 py-3 font-medium">Registered</th>
                        @if ($tab === 'rejected')
                            <th class="px-4 py-3 font-medium">Reason</th>
                        @endif
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($developers as $dev)
                        <tr class="border-b border-border align-top last:border-0">
                            <td class="px-4 py-3 font-medium">{{ $dev->name }}</td>
                            <td class="px-4 py-3">{{ $dev->email }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $dev->developer?->studio_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-subtle">{{ $dev->created_at->format('d M Y') }}</td>
                            @if ($tab === 'rejected')
                                <td class="px-4 py-3 text-danger">{{ $dev->rejection_reason }}</td>
                            @endif
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('admin.verification.update', $dev) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn-primary px-3 py-1.5 text-sm">Approve</button>
                                    </form>
                                    @if ($tab !== 'rejected')
                                        <details>
                                            <summary class="btn-ghost cursor-pointer px-3 py-1.5 text-sm">Reject</summary>
                                            <form method="POST" action="{{ route('admin.verification.update', $dev) }}" class="mt-2 flex flex-col gap-2">
                                                @csrf
                                                <input type="hidden" name="action" value="reject">
                                                <textarea name="reason" rows="2" required maxlength="500" placeholder="Rejection reason" class="input w-64 text-sm"></textarea>
                                                @error('reason')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                                                <button class="btn-ghost w-fit px-3 py-1.5 text-sm text-danger">Confirm reject</button>
                                            </form>
                                        </details>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $developers->links() }}</div>
</x-portal-layout>
