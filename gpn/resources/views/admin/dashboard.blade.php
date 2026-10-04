@extends('admin.layout')
@section('title', 'Admin — Overview')

@section('admin')
<h1 class="text-3xl font-semibold">Overview</h1>
<p class="mt-1 text-gpn-fg-muted">Platform-wide health at a glance.</p>

<div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-stat-tile icon="users" label="Pending Developers" :value="$pendingDevelopers" />
    <x-stat-tile icon="monitor" label="Games Awaiting Moderation" :value="$pendingGames" />
    <x-stat-tile icon="cart" label="Total Transactions" :value="number_format($transactions)" />
    <x-stat-tile icon="tag" label="Gross Volume" :value="'Rp '.number_format($gross, 0, ',', '.')" />
</div>

<div class="mt-8 grid gap-4 md:grid-cols-3">
    <a href="{{ route('admin.users') }}" class="gpn-panel p-6 transition hover:border-gpn-border-strong">
        <h2 class="font-medium">Verifikasi Akun</h2>
        <p class="mt-2 text-sm text-gpn-fg-muted">
            {{ $pendingDevelopers }} akun Developer menunggu persetujuan.
        </p>
    </a>
    <a href="{{ route('admin.moderation') }}" class="gpn-panel p-6 transition hover:border-gpn-border-strong">
        <h2 class="font-medium">Moderasi Game</h2>
        <p class="mt-2 text-sm text-gpn-fg-muted">
            {{ $pendingGames }} game menunggu tinjauan sebelum tayang.
        </p>
    </a>
    <a href="{{ route('admin.reports') }}" class="gpn-panel p-6 transition hover:border-gpn-border-strong">
        <h2 class="font-medium">Laporan Transaksi</h2>
        <p class="mt-2 text-sm text-gpn-fg-muted">Rekap transaksi, komisi, dan pendapatan platform.</p>
    </a>
</div>
@endsection
