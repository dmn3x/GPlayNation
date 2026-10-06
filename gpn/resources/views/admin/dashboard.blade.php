<x-portal-layout title="Admin Dashboard" section="admin">
    <div class="pb-8">
        <h1 class="text-2xl font-bold tracking-tight text-white">Dashboard</h1>
        <p class="mt-1 text-sm text-white/50">Platform overview at a glance.</p>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <a href="{{ route('admin.verification.index') }}" class="card p-5 transition hover:border-accent">
            <p class="text-sm text-faint">Pending developers</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($pendingDevelopers) }}</p>
        </a>
        <a href="{{ route('admin.moderation.index') }}" class="card p-5 transition hover:border-accent">
            <p class="text-sm text-faint">Pending games</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($pendingGames) }}</p>
        </a>
        <div class="card p-5">
            <p class="text-sm text-faint">Users</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($totalUsers) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Today's transactions</p>
            <p class="mt-1 text-2xl font-semibold">{{ number_format($todayTransactions) }}</p>
        </div>
        <div class="card p-5">
            <p class="text-sm text-faint">Commission this month</p>
            <p class="mt-1 text-2xl font-semibold">{{ \App\Support\Money::format($commissionThisMonth) }}</p>
        </div>
    </div>
</x-portal-layout>
