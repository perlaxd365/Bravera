{{-- Miniatura de producto para los correos.

     Los correos se renderizan en clientes que ignoran las hojas de estilo
     externas, así que el tamaño y los bordes van como estilo en línea y como
     atributos width/height para el ancho de columna de Outlook. --}}
@props([
    'src' => null,
    'alt' => '',
    'size' => 56,
    'radius' => 8,
])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $size }}" border="0"
        style="display:block;width:{{ $size }}px;height:auto;max-width:100%;border:1px solid #e5e7eb;border-radius:{{ $radius }}px;-ms-interpolation-mode:bicubic;">
@endif
