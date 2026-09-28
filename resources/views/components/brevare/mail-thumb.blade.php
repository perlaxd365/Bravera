{{-- Miniatura de producto para los correos.

     Los correos se renderizan en clientes que ignoran las hojas de estilo
     externas, así que el tamaño y los bordes van como estilo en línea y como
     atributos width/height para el ancho de columna de Outlook. --}}
@props([
    'src' => null,
    'alt' => '',
    'size' => 56,
    'radius' => 12,
])

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" width="{{ $size }}" height="{{ $size }}"
        style="display:block;width:{{ $size }}px;height:{{ $size }}px;border:1px solid #e5e7eb;border-radius:{{ $radius }}px;object-fit:cover;-ms-interpolation-mode:bicubic;">
@endif
