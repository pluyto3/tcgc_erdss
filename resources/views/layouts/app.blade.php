@php
    $module = $module ?? request()->route('module') ?? 'dashboard';
    $title = $title ?? config('modules.'.$module.'.title', 'Retention overview');
    $user = auth()->user();
    $navigation = collect(config('modules'))->filter(fn ($item) => in_array(role(), $item['roles'], true))->groupBy('group', preserveKeys: true);
    $demo = $settings->get('demo_mode', '1') === '1';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="color-scheme" content="light">
    <title>{{ $title }} · {{ $settings->get('system_name', 'TCGC Retention DSS') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}?v=4">
    <script src="{{ asset('assets/app.js') }}?v=2" defer></script>
    <x-brand-theme />
</head>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="sidebar-overlay" data-close-nav></div>
<aside class="sidebar" id="main-navigation" aria-label="Main navigation">
    <button type="button" class="icon-button mobile-nav-close" data-close-nav aria-label="Close navigation">×</button>
    <a href="{{ route('dashboard') }}" class="brand">
        <x-institution-logo />
        <span><strong>TCGC<span class="brand-dot">.</span></strong><small>RETENTION DSS</small></span>
    </a>
    <div class="institution-label">{{ $settings->get('institution_name', 'Tangub City Global College') }}</div>
    <nav class="nav-scroll">
        @foreach($navigation as $group => $items)
            <div class="nav-group-label">{{ $group }}</div>
            @foreach($items as $key => $item)
                <a href="{{ route($key) }}" class="nav-link {{ $module === $key ? 'active' : '' }}" @if($module === $key) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" /><span>{{ $item['title'] }}</span>
                    @if($module === $key)<span class="nav-active-dot"></span>@endif
                </a>
            @endforeach
        @endforeach
    </nav>
    <div class="sidebar-bottom"><div class="security-note"><x-icon name="shield" /><span>Private & secure workspace</span></div>
        <div class="user-panel"><div class="avatar">{{ mb_substr($user->name, 0, 1) }}</div><div><strong>{{ $user->name }}</strong><small>{{ role() }}</small></div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="icon-button" aria-label="Sign out" title="Sign out"><x-icon name="logout" /></button></form>
        </div>
    </div>
</aside>
<div class="main-shell">
    <header class="topbar">
        <div class="breadcrumb"><button class="icon-button menu-toggle" data-toggle-nav aria-controls="main-navigation" aria-expanded="false" aria-label="Open navigation"><x-icon name="menu" /></button><span>Workspace</span><x-icon name="chevron" /><strong>{{ $title }}</strong></div>
        <div class="topbar-right"><span class="date-label"><x-icon name="calendar" />{{ now()->format('M d, Y') }}</span><span class="workspace-status"><i></i> {{ $demo ? 'Demo workspace' : 'Institution workspace' }}</span><div class="avatar avatar-small">{{ mb_substr($user->name, 0, 1) }}</div></div>
    </header>
    <main id="main-content" class="content" tabindex="-1">
        @if($errors->any())<div class="alert error" role="alert"><strong>Please check the following.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @foreach(session()->pull('messages', []) as $message)<div class="alert {{ $message['type'] }}" role="status">{{ $message['message'] }}</div>@endforeach
        @if(session('status'))<div class="alert success" role="status">{{ session('status') }}</div>@endif
        @hasSection('content')@yield('content')
        @else
            <div class="page-heading"><div><div class="eyebrow">{{ config('modules.'.$module.'.group', 'Workspace') }}</div><h1>{{ $title }}</h1><p>{{ config('modules.'.$module.'.description', 'Review your institution’s employee retention information.') }}</p></div></div>
            @if($demo)<div class="context-note"><x-icon name="shield" /><span>Demonstration workspace · Employee and survey records are synthetic.</span></div>@endif
            <div class="module-content">{!! $content ?? '' !!}</div>
        @endif
    </main>
    <footer class="footer"><span>© {{ now()->year }} {{ $settings->get('institution_name', 'Tangub City Global College') }}</span><span>Employee Retention Decision Support System <span class="footer-separator">·</span> Aggregate insights. Informed decisions.</span></footer>
</div>
</body>
</html>
