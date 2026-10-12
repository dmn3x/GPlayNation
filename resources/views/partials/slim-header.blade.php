@php $active = $active ?? 'profile'; @endphp
<header class="bg-[#161822]">
    <div class="mx-auto flex h-14 max-w-[1510px] items-center justify-center px-4 md:h-20 md:px-12">
        <nav class="flex items-center gap-2 overflow-x-auto md:gap-12">
            <a href="{{ route('store.index') }}" class="whitespace-nowrap rounded-full px-3 py-2 text-[15px] font-medium backdrop-blur-md transition md:px-4 md:text-[21px] {{ $active === 'store' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Store</a>
            <a href="{{ route('community.index') }}" class="whitespace-nowrap rounded-full px-3 py-2 text-[15px] font-medium backdrop-blur-md transition md:px-4 md:text-[21px] {{ $active === 'community' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Community</a>
            <a href="{{ route('profile.show') }}" class="whitespace-nowrap rounded-full px-3 py-2 text-[15px] font-medium backdrop-blur-md transition md:px-4 md:text-[21px] {{ $active === 'profile' ? 'text-white hover:bg-white/10' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">Profile</a>
        </nav>
    </div>
</header>
