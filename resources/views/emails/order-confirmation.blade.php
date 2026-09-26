@php
    $customerName = $order->customer_snapshot['name'] ?? $order->user?->name ?? '';
    $items = $order->items;
@endphp

<x-bravera.mail-layout :title="'Pedido '.$order->order_number.' confirmado'" heading="¡Tu pedido fue confirmado!" accent="success">
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

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;border-collapse:separate;">

        <thead>
            <tr style="background-color:#f9fafb;">
                <th align="left" style="padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;">
                    Producto
                </th>
                <th align="center" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:52px;">
                    Cant.
                </th>
                <th align="right" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:82px;">
                    P. unit.
                </th>
                <th align="right" style="padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:92px;">
                    Subtotal
                </th>
            </tr>
        </thead>

        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td style="padding:12px 14px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;vertical-align:top;">
                        {{ $item->product_name }}
                        @if ($item->variant_attributes)
                            <br>
                            <span style="font-size:12px;color:#6b7280;">
                                {{ collect($item->variant_attributes)->filter()->map(fn ($v) => is_array($v) ? ($v['value'] ?? '') : $v)->implode(' · ') }}
                            </span>
                        @endif
                        @if ($item->supplier_name)
                            <br>
                            <span style="font-size:11px;color:#9ca3af;">
                                Proveedor: {{ $item->supplier_name }}
                            </span>
                        @endif
                    </td>
                    <td align="center" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;">
                        {{ $item->quantity }}
                    </td>
                    <td align="right" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#4b5563;white-space:nowrap;">
                        S/ {{ number_format((float) $item->unit_price, 2) }}
                    </td>
                    <td align="right" style="padding:12px 14px;border-top:1px solid #f3f4f6;font-size:14px;font-weight:600;color:#111827;white-space:nowrap;">
                        S/ {{ number_format((float) $item->line_subtotal, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

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
</x-bravera.mail-layout>
