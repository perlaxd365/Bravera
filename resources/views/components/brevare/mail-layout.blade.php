@props([
    'title' => null,
    'heading' => null,
    'accent' => 'primary',
    'preheader' => null,
])

@php
    $palette = [
        'primary' => ['#111827', '#1f2937'],
        'success' => ['#047857', '#065f46'],
        'danger' => ['#b91c1c', '#991b1b'],
        'warning' => ['#b45309', '#92400e'],
        'info' => ['#1d4ed8', '#1e40af'],
        'neutral' => ['#374151', '#1f2937'],
    ];
    [$bg, $fg] = $palette[$accent] ?? $palette['primary'];

    // Los clientes de correo toman de este bloque oculto el texto que se ve en la
    // bandeja de entrada. Debe ser texto plano: si se escapara el HTML del slot,
    // la vista previa mostraría las etiquetas en lugar del contenido del correo.
    $preview = \Illuminate\Support\Str::of($preheader ?? html_entity_decode(strip_tags((string) $slot)))
        ->replaceMatches('/\s+/u', ' ')
        ->trim()
        ->substr(0, 150)
        ->toString();
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? 'Brevare' }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .mail-outer { padding: 12px 6px !important; }
            .mail-header { padding: 22px 18px !important; }
            .mail-content { padding: 22px 18px !important; }
            .mail-footer { padding: 18px !important; }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#1f2937;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        {{ $preview }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        class="mail-outer" style="background-color:#f5f5f7;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:600px;background-color:#ffffff;border-radius:20px;border:1px solid #e5e7eb;">

                    <tr>
                        <td class="mail-header" style="background-color:{{ $bg }};padding:28px 32px;text-align:center;">
                            <p style="margin:0;font-size:13px;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.7);">
                                Brevare
                            </p>
                            @if ($heading)
                                <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;font-weight:600;color:#ffffff;">
                                    {{ $heading }}
                                </h1>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="mail-content" style="padding:32px;">
                            {{ $slot }}
                        </td>
                    </tr>

                    <tr>
                        <td class="mail-footer" style="background-color:#fafafa;border-top:1px solid #f3f4f6;padding:20px 32px;text-align:center;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#9ca3af;">
                                Brevare · Perú<br>
                                Este es un mensaje automático, por favor no lo respondas.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
