@php
    $customerName = $cart->user?->name ?? '';
    $items = $cart->items;
@endphp

<x-brevare.mail-layout
    title="Tu carrito en Brevare te espera"
    heading="Tus productos siguen aquí"
    preheader="Retoma tu compra en Brevare y termina el checkout con los productos que elegiste.">

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $customerName }}</strong>,
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
        Vimos que dejaste estos productos en tu carrito. Todavía puedes retomar tu compra
        y terminar el checkout cuando quieras.
    </p>

    <h2 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#111827;">
        Tu carrito ({{ $items->sum('quantity') }} {{ $items->sum('quantity') === 1 ? 'producto' : 'productos' }})
    </h2>

    @foreach ($items as $item)
        @php
            $variant = $item->variant;
            $product = $variant?->product;
            $image = $variant?->images->firstWhere('is_primary', true) ?? $variant?->images->first();
            $imageUrl = $image?->sizedUrl(240) ?? $image?->imageUrl() ?? $product?->coverImage();
            $variantLabel = $variant?->attributeValues
                ?->map(fn ($pivot) => $pivot->value?->value)
                ->filter()
                ->implode(' · ');
        @endphp

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
            style="width:100%;margin:0 0 12px;border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;">
            <tr>
                <td width="88" valign="top" style="width:88px;padding:12px 10px 12px 12px;">
                    <x-brevare.mail-thumb :src="$imageUrl" :alt="$product?->name ?? 'Producto'" :size="72" />
                </td>
                <td valign="top" style="padding:12px 12px 12px 0;word-break:break-word;font-size:14px;line-height:1.5;color:#111827;">
                    <strong>{{ $product?->name ?? 'Producto' }}</strong>
                    @if ($variantLabel)
                        <br><span style="font-size:12px;color:#6b7280;">{{ $variantLabel }}</span>
                    @endif
                    <br><span style="color:#6b7280;">
                        Cantidad: {{ $item->quantity }} · Unitario: S/ {{ number_format((float) $item->unit_price, 2) }}
                    </span>
                    <br><strong>Subtotal: S/ {{ number_format((float) $item->lineSubtotal(), 2) }}</strong>
                </td>
            </tr>
        </table>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px;font-size:14px;">
        <tr>
            <td style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">Subtotal</td>
            <td align="right" style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">
                S/ {{ number_format((float) $cart->subtotal(), 2) }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:4px 0 0;font-size:12px;color:#9ca3af;">
                El envío y los descuentos se calculan al retomar el checkout.
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 20px;">
        <tr>
            <td align="center" style="border-radius:999px;background-color:#111827;">
                <a href="{{ $recoveryUrl }}"
                    style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">
                    Retomar mi compra
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;text-align:center;font-size:12px;line-height:1.6;color:#9ca3af;">
        Si el botón no funciona, copia este enlace en tu navegador:
    </p>
    <p style="margin:0 0 24px;text-align:center;font-size:11px;line-height:1.5;word-break:break-all;color:#6b7280;">
        <a href="{{ $recoveryUrl }}" style="color:#6b7280;text-decoration:underline;">{{ $recoveryUrl }}</a>
    </p>

    <p style="margin:0;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;">
        Si ya completaste tu compra, ignora este correo.
        @if ($unsubscribeUrl)
            <br><a href="{{ $unsubscribeUrl }}" style="color:#9ca3af;text-decoration:underline;">No quiero recibir recordatorios de carrito</a>
        @endif
    </p>

    <p style="margin:16px 0 0;font-size:12px;line-height:1.6;color:#9ca3af;text-align:center;">
        ¿Necesitas ayuda? Escríbenos a
        <a href="mailto:{{ $company['support_email'] }}" style="color:#6b7280;text-decoration:underline;">{{ $company['support_email'] }}</a>.
    </p>
</x-brevare.mail-layout>
