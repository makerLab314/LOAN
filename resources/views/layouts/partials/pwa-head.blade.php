{{-- Als App installierbar (Android/Chrome/Edge über das Manifest, iOS über "Zum Home-Bildschirm") --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<link rel="apple-touch-icon" href="{{ route('pwa.icon.maskable', '180') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $branding->name() }}">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
