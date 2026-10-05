@php $active = $active ?? 'profile'; @endphp
<header class="bg-[#161822]">
    <div class="mx-auto flex h-20 max-w-[1510px] items-center justify-center px-12">
        <nav class="flex items-center gap-12">
            <a href="{{ route('store.index') }}" class="rounded-full px-4 py-2 text-[21px] font-medium backdrop-blur-md transition {{ $active === 'store' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Store</a>
            <a href="{{ route('community.index') }}" class="rounded-full px-4 py-2 text-[21px] font-medium backdrop-blur-md transition {{ $active === 'community' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Community</a>
            <a href="{{ route('profile.show') }}" class="rounded-full px-4 py-2 text-[21px] font-medium backdrop-blur-md transition {{ $active === 'profile' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Profile</a>
        </nav>
    </div>
</header>
