@php
    $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? '';
    $items = $order->items;
    $thumb = 72;
@endphp

<x-brevare.mail-layout :title="'Pedido '.$order->order_number.' confirmado'" heading="¡Tu pedido fue confirmado!" accent="success"
    preheader="Tu pedido {{ $order->order_number }} por S/ {{ number_format((float) $order->total, 2) }} fue confirmado.">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $customerName }}</strong>,
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
        Gracias por tu compra. Tu pedido <strong style="color:#111827;">{{ $order->order_number }}</strong>
        por <strong style="color:#111827;">S/ {{ number_format((float) $order->total, 2) }}</strong>
        ya fue pagado y estamos preparando la entrega con nuestros proveedores.
    </p>

    <h2 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#111827;">
        Productos ({{ $items->count() }})
    </h2>

    @foreach ($items as $item)
        @php $image = $item->imageUrl(240); @endphp
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
            style="width:100%;margin:0 0 12px;border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;">
            <tr>
                <td width="88" valign="top" style="width:88px;padding:12px 10px 12px 12px;">
                    <x-brevare.mail-thumb :src="$image" :alt="$item->product_name" :size="$thumb" />
                </td>
                <td valign="top" style="padding:12px 12px 12px 0;word-break:break-word;font-size:14px;line-height:1.5;color:#111827;">
                    <strong>{{ $item->product_name }}</strong>
                    @if ($item->variant_attributes)
                        <br><span style="font-size:12px;color:#6b7280;">
                            {{ collect($item->variant_attributes)->filter()->map(fn ($v) => is_array($v) ? ($v['value'] ?? '') : $v)->implode(' · ') }}
                        </span>
                    @endif
                    @if ($item->supplier_name)
                        <br><span style="font-size:11px;color:#9ca3af;">Proveedor: {{ $item->supplier_name }}</span>
                    @endif
                    <br><span style="color:#6b7280;">Cantidad: {{ $item->quantity }} · Unitario: S/ {{ number_format((float) $item->unit_price, 2) }}</span>
                    <br><strong>Subtotal: S/ {{ number_format((float) $item->line_subtotal, 2) }}</strong>
                </td>
            </tr>
        </table>
    @endforeach

    <div style="height:12px;line-height:12px;">&nbsp;</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;font-size:14px;">
        <tr>
            <td style="padding:4px 0;color:#6b7280;">Subtotal</td>
            <td align="right" style="padding:4px 0;color:#111827;">S/ {{ number_format((float) $order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td style="padding:4px 0;color:#6b7280;">Envío</td>
            <td align="right" style="padding:4px 0;color:#111827;">S/ {{ number_format((float) $order->shipping_total, 2) }}</td>
        </tr>
        @if ((float) $order->discount_total > 0)
            <tr>
                <td style="padding:4px 0;color:#6b7280;">Cupón {{ $order->coupon_code }}</td>
                <td align="right" style="padding:4px 0;color:#047857;">- S/ {{ number_format((float) $order->discount_total, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">Total</td>
            <td align="right" style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">
                S/ {{ number_format((float) $order->total, 2) }}
            </td>
        </tr>
    </table>

    <h2 style="margin:0 0 8px;font-size:15px;font-weight:600;color:#111827;">Dirección de entrega</h2>
    <p style="margin:0 0 24px;font-size:14px;line-height:1.7;color:#4b5563;">
        {{ $order->address_snapshot['full_name'] ?? '' }}<br>
        {{ $order->address_snapshot['address'] ?? '' }}
        @if ($order->address_snapshot['reference'] ?? null)
            ({{ $order->address_snapshot['reference'] }})
        @endif
        <br>
        {{ $order->address_snapshot['location_label'] ?? '' }}<br>
        {{ $order->address_snapshot['phone'] ?? '' }}
    </p>

    <p style="margin:0;text-align:center;">
        <a href="{{ route('account.orders', [], true) }}"
            style="display:inline-block;background-color:#111827;color:#ffffff;padding:12px 24px;border-radius:9999px;text-decoration:none;font-size:14px;font-weight:600;">
            Ver mis pedidos
        </a>
    </p>
</x-brevare.mail-layout>
