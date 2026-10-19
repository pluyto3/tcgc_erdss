@props(['name' => 'dashboard'])
@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'chart' => '<path d="M4 4v16h17M8 15l4-5 4 3 5-8"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>',
        'message' => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-2 2v-9.5a8.5 8.5 0 0 1 17-1ZM8 9h8M8 13h5"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'activity' => '<path d="M2 12h5l3-8 4 16 3-8h5"/>',
        'report' => '<path d="M14 3H5v18h14V8l-5-5Zm0 0v5h5M8 12h8M8 16h6"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'clipboard' => '<rect x="5" y="5" width="14" height="16" rx="2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-5"/>',
        'layers' => '<path d="m12 3 10 5-10 5L2 8l10-5ZM2 12l10 5 10-5M2 16l10 5 10-5"/>',
        'bulb' => '<path d="M9 18h6M10 21h4M8 14a6 6 0 1 1 8 0l-1 4H9l-1-4Z"/>',
        'building' => '<path d="M4 21V3h16v18M2 21h20M9 21v-5h6v5M8 7h1M15 7h1M8 11h1M15 11h1"/>',
        'sliders' => '<path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="15" cy="17" r="3"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Zm-4 9 3 3 5-6"/>',
        'history' => '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 7v5l3 2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 3 2 2-1 4 3 2 3-1 2 4 3-1 1-3 4-1 1-3-3-2 1-3-3-2-3 1-1-3H9Z"/>',
        'wallet' => '<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 8h18M16 12h5v4h-5z"/>',
        'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'logout' => '<path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['dashboard'] !!}</svg>
