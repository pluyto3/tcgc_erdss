<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · TCGC Retention DSS</title><link rel="stylesheet" href="{{ asset('assets/style.css') }}?v=2"><script src="{{ asset('assets/app.js') }}?v=2" defer></script></head>
<body class="login-page">
<main class="login-layout">
    <section class="login-story"><a class="brand" href="{{ route('login') }}"><span class="brand-mark"><x-icon name="layers" /></span><span><strong>TCGC<span class="brand-dot">.</span></strong><small>RETENTION DSS</small></span></a><div class="login-story-body"><div class="eyebrow">PEOPLE AT THE CENTER</div><h1>A stronger workplace<br>starts with understanding.</h1><p>Turn employee perspectives into clear priorities, thoughtful action, and lasting growth.</p><div class="login-illustration"><div><x-icon name="users" /><span>Employee experience</span><strong>Listen.</strong></div><div><x-icon name="chart" /><span>Meaningful insights</span><strong>Understand.</strong></div><div><x-icon name="target" /><span>Informed decisions</span><strong>Act.</strong></div></div></div><small>Tangub City Global College · Employee Retention Decision Support System</small></section>
    <section class="login-form-panel"><div class="login-box"><span class="login-welcome">YOUR INSTITUTION WORKSPACE</span><h2>Welcome back</h2><p class="muted">Sign in to continue to your dashboard.</p>
        @if(session('status'))<div class="alert success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.store') }}">@csrf
            <div class="form-group"><label for="email">Email address</label><input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@tcgc.edu.ph" required autofocus @error('email') aria-invalid="true" @enderror></div>
            <div class="form-group"><label for="password">Password</label><div class="password-wrap"><input type="password" name="password" id="password" autocomplete="current-password" required><button type="button" class="password-toggle" data-toggle-password aria-controls="password" aria-pressed="false">Show</button></div></div>
            <button class="btn btn-primary login-submit">Sign in to workspace <x-icon name="arrow" /></button>
        </form>
        <div class="login-privacy"><x-icon name="shield" /><span>Your access is based on your institutional role.</span></div>
        @if($settings->get('demo_mode', '1') === '1' && app()->environment('local'))
            <details class="demo-credentials"><summary>Explore the demonstration workspace</summary><p>All operational records are synthetic.</p><dl><dt>HR administrator</dt><dd>hr.admin@tcgc-demo.local</dd><dt>Executive</dt><dd>executive@tcgc-demo.local</dd><dt>Unit head</dt><dd>dept.head@tcgc-demo.local</dd><dt>Employee</dt><dd>employee@tcgc-demo.local</dd></dl><p>Password: <code>{{ env('DEMO_PASSWORD', 'DemoPass123!') }}</code></p></details>
        @endif
    </div><small class="login-copyright">© {{ now()->year }} Tangub City Global College</small></section>
</main></body></html>
