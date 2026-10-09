@php
    // Versión por fecha de archivo para forzar recarga cuando cambia el icono.
    $faviconVersion = fn (string $path): ?int => is_file(public_path($path)) ? filemtime(public_path($path)) : null;

    // SVG embebido como data-URI: se muestra incluso si el servidor no sirve
    // /favicon.svg correctamente o si un CDN cachea los archivos estáticos.
    $faviconSvgPath = public_path('favicon.svg');
    $faviconSvgData = is_file($faviconSvgPath)
        ? 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($faviconSvgPath))
        : null;

    $icoVersion = $faviconVersion('favicon.ico');
    $appleVersion = $faviconVersion('apple-touch-icon.png');
    $faviconIcoUrl = asset('favicon.ico').($icoVersion ? '?v='.$icoVersion : '');
    $appleTouchUrl = asset('apple-touch-icon.png').($appleVersion ? '?v='.$appleVersion : '');
@endphp

@if ($faviconSvgData)
    <link rel="icon" type="image/svg+xml" href="{{ $faviconSvgData }}">
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
@endif
<link rel="icon" href="{{ $faviconIcoUrl }}" sizes="any">
<link rel="shortcut icon" href="{{ $faviconIcoUrl }}">
<link rel="apple-touch-icon" href="{{ $appleTouchUrl }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="#FCD34D">
