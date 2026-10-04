@props(['name', 'class' => 'h-5 w-5'])
@php
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'store' => '<path d="M4 9h16v11H4z"/><path d="M4 9 6 4h12l2 5"/><path d="M9 13h6"/>',
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.2a3.5 3.5 0 0 1 0 5.6"/><path d="M17.5 14.2A6.5 6.5 0 0 1 21.5 20"/>',
        'flame' => '<path d="M12 21c3.6 0 6.5-2.7 6.5-6.2 0-4.6-4.3-6.4-4.3-10.3 0 0-2.9 1.6-2.9 5 0 1.7-1.1 2.3-1.9 1.6-.7-.6-.9-1.6-.9-1.6S5.5 11 5.5 14.8C5.5 18.3 8.4 21 12 21Z"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5.2-5.2 2 2-5.2 5.2-2Z"/>',
        'trophy' => '<path d="M7 4h10v5a5 5 0 0 1-10 0V4Z"/><path d="M7 6H4.5v1.5A3 3 0 0 0 7 10.5"/><path d="M17 6h2.5v1.5a3 3 0 0 1-2.5 3"/><path d="M12 14v4"/><path d="M8.5 20h7"/>',
        'bookmark' => '<path d="M6.5 4h11v17L12 16.5 6.5 21V4Z"/>',
        'user' => '<circle cx="12" cy="8.5" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.2 5.2l2.1 2.1M16.7 16.7l2.1 2.1M18.8 5.2l-2.1 2.1M7.3 16.7l-2.1 2.1"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chart' => '<path d="M4 19h16"/><path d="M4 15l4-5 3.5 3L20 5"/>',
        'tag' => '<path d="M4 11.5V5a1 1 0 0 1 1-1h6.5L20 12.5 12.5 20 4 11.5Z"/><circle cx="8.5" cy="8.5" r="1.5"/>',
        'download' => '<path d="M12 4v10"/><path d="m8 10.5 4 4 4-4"/><path d="M5 19h14"/>',
        'cart' => '<path d="M3 4h2.5l2.2 11h10.3l2-7.5H6.5"/><circle cx="9.5" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/>',
        'chevron-down' => '<path d="m7 10 5 5 5-5"/>',
        'chevron-left' => '<path d="m14 6-6 6 6 6"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m11 6-6 6 6 6"/>',
        'filter' => '<path d="M4 5h16l-6.2 7.3V19l-3.6 2v-8.7L4 5Z"/>',
        'bolt' => '<path d="M13.5 3 5 13.5h5L9.5 21 19 10.5h-5.5L13.5 3Z"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'monitor' => '<rect x="3" y="4.5" width="18" height="12" rx="2"/><path d="M9 20h6"/>',
        'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="2"/><path d="M3.5 10h17"/><path d="M8 3.5v4M16 3.5v4"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'edit' => '<path d="M4 20h4l10-10-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'trash' => '<path d="M4.5 7h15"/><path d="M9 7V4.5h6V7"/><path d="M6.5 7 7.5 20h9l1-13"/>',
        'heart' => '<path d="M12 20s-7.5-4.7-7.5-9.7A4.3 4.3 0 0 1 12 7.4a4.3 4.3 0 0 1 7.5 2.9c0 5-7.5 9.7-7.5 9.7Z"/>',
        'google' => '<path d="M21 12.2c0-.7-.1-1.4-.2-2H12v3.9h5.1a4.4 4.4 0 0 1-1.9 2.8v2.3h3.1A9 9 0 0 0 21 12.2Z" fill="currentColor" stroke="none"/><path d="M12 21.5c2.5 0 4.6-.8 6.1-2.3l-3.1-2.3a5.6 5.6 0 0 1-8.3-2.9H3.6v2.4A9.5 9.5 0 0 0 12 21.5Z" fill="currentColor" stroke="none" opacity=".85"/><path d="M6.7 14a5.7 5.7 0 0 1 0-3.5V8.1H3.6a9.5 9.5 0 0 0 0 8.3L6.7 14Z" fill="currentColor" stroke="none" opacity=".7"/><path d="M12 6.1a5.2 5.2 0 0 1 3.7 1.5l2.8-2.8A9.5 9.5 0 0 0 3.6 8.1L6.7 11a5.7 5.7 0 0 1 5.3-4.9Z" fill="currentColor" stroke="none" opacity=".95"/>',
        'apple' => '<path d="M16.4 12.6c0-2.4 2-3.6 2.1-3.6-1.1-1.7-2.9-1.9-3.5-1.9-1.5-.2-2.9.9-3.6.9-.8 0-1.9-.9-3.1-.8-1.6 0-3.1.9-3.9 2.4-1.7 2.9-.4 7.2 1.2 9.5.8 1.2 1.7 2.4 3 2.4 1.2 0 1.6-.8 3.1-.8s1.9.8 3.1.7c1.3 0 2.1-1.2 2.9-2.3.9-1.3 1.3-2.6 1.3-2.7 0 0-2.5-1-2.6-3.8Z" fill="currentColor" stroke="none"/><path d="M14.1 5.4c.6-.8 1.1-1.9 1-3-1 0-2.2.7-2.9 1.5-.6.7-1.1 1.8-1 2.9 1.1.1 2.2-.6 2.9-1.4Z" fill="currentColor" stroke="none"/>',
        'upload' => '<path d="M12 20V9"/><path d="m8 12.5 4-4 4 4"/><path d="M5 5h14"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 4v4.5h-4.5"/>',
        'logout' => '<path d="M14 7V5H4v14h10v-2"/><path d="M9 12h11"/><path d="m17 9 3 3-3 3"/>',
        'star' => '<path d="m12 3.5 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3.5Z" fill="currentColor" stroke="none"/>',
    ];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => $class]) }}
     aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
