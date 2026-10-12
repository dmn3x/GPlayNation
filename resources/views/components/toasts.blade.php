@if (session('success') || session('error'))
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2" role="status">
        @if (session('success'))
            <div data-toast class="card flex items-center gap-3 !rounded-xl px-4 py-3 border-success/40">
                <x-icon name="check" class="h-5 w-5 text-success" /><span>{{ session('success') }}</span>
                <button type="button" onclick="this.closest('[data-toast]').remove()" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4 text-subtle" /></button>
            </div>
        @endif
        @if (session('error'))
            <div data-toast class="card flex items-center gap-3 !rounded-xl px-4 py-3 border-danger/60">
                <x-icon name="alert" class="h-5 w-5 text-danger" /><span>{{ session('error') }}</span>
                <button type="button" onclick="this.closest('[data-toast]').remove()" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4 text-subtle" /></button>
            </div>
        @endif
    </div>
    <script>setTimeout(() => document.querySelectorAll('[data-toast]').forEach(t => t.remove()), 5000);</script>
@endif
