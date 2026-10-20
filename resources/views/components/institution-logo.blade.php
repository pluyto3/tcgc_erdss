@php
    $brandingSettings = app(\App\Services\SettingsService::class);
    $uploadedLogo = $brandingSettings->get('logo_path', '');
    $logoPath = $uploadedLogo && preg_match('~^uploads/[a-zA-Z0-9._-]+$~', $uploadedLogo) && is_file(public_path($uploadedLogo))
        ? $uploadedLogo
        : config('branding.logo');
@endphp
<img {{ $attributes->merge(['class' => 'brand-mark institution-logo']) }} src="{{ asset($logoPath) }}" alt="{{ $brandingSettings->get('institution_name', 'Tangub City Global College') }} logo" width="72" height="72">
