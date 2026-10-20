@php
    $brandingSettings = app(\App\Services\SettingsService::class);
    $primaryColor = $brandingSettings->get('primary_color', config('branding.primary_color'));
    $accentColor = $brandingSettings->get('accent_color', config('branding.accent_color'));
    $primaryColor = preg_match('/^#[a-f0-9]{6}$/i', $primaryColor) ? $primaryColor : config('branding.primary_color');
    $accentColor = preg_match('/^#[a-f0-9]{6}$/i', $accentColor) ? $accentColor : config('branding.accent_color');
@endphp
<link rel="icon" type="image/png" href="{{ asset(config('branding.logo')) }}">
<style>:root { --brand-primary: {{ $primaryColor }}; --brand-accent: {{ $accentColor }}; }</style>
