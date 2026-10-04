@extends('developer.layout')
@section('title', 'Settings — Developer Tools')

@section('dev')
<h1 class="text-3xl font-semibold">Settings</h1>
<p class="mt-1 text-gpn-fg-muted">Manage your studio profile.</p>

<div class="gpn-panel mt-6 max-w-lg p-7">
    <form method="POST" action="{{ route('developer.settings.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="nama_developer" class="gpn-label">Developer name</label>
            <input id="nama_developer" name="nama_developer" required class="gpn-input" value="{{ old('nama_developer', $developer->nama_developer) }}">
        </div>
        <div>
            <label for="nama_studio" class="gpn-label">Studio name</label>
            <input id="nama_studio" name="nama_studio" required class="gpn-input" value="{{ old('nama_studio', $developer->nama_studio) }}">
        </div>
        <div>
            <label for="developer_portofolio" class="gpn-label">Portfolio</label>
            <textarea id="developer_portofolio" name="developer_portofolio" rows="4" class="gpn-input">{{ old('developer_portofolio', $developer->developer_portofolio) }}</textarea>
        </div>
        <button class="gpn-btn gpn-btn-primary w-full">Save</button>
    </form>
</div>
@endsection
