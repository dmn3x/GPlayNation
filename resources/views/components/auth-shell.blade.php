@props(['wide' => false])
<div class="auth-shell px-6 py-12">
    <div class="auth-blob h-64 w-64 opacity-10 blur-[200px]" style="left:180px;top:150px" aria-hidden="true"></div>
    <div class="auth-blob h-64 w-64 opacity-5 blur-[160px]" style="left:507px;top:263px" aria-hidden="true"></div>
    <div class="relative {{ $wide ? 'w-[518px] p-[43.2px] card-auth-lg' : 'w-[420px] p-[36px] card-auth' }} max-w-[calc(100vw-3rem)]">
        {{ $slot }}
    </div>
</div>
