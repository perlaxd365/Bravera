@php
    $shipping = $order->shippingDetails();
    $customerOrder = $order->order;
    $totalShipping = round(
        $order->items->sum(fn ($item) => (float) $item->supplier_shipping_cost * (int) $item->quantity),
        2
    );
    $thumb = 52;
@endphp

<x-brevare.mail-layout :title="'Orden '.$order->supplier_order_number" heading="Nueva orden de compra" accent="primary"
    :preheader="'Debes despachar '.$order->items->count().' producto(s) por S/ '.number_format((float) $order->total_cost, 2).'.'">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $order->supplier?->business_name }}</strong>,
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
        Se generó la orden <strong style="color:#111827;">{{ $order->supplier_order_number }}</strong>
        por un total de <strong style="color:#111827;">S/ {{ number_format((float) $order->total_cost, 2) }}</strong>.
    </p>

    <h2 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#111827;">
        Productos a despachar ({{ $order->items->count() }})
    </h2>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;border-collapse:separate;">
        <thead>
            <tr style="background-color:#f9fafb;">
                <th width="{{ $thumb + 20 }}" style="padding:0;font-size:11px;line-height:1px;">
                    <span style="display:none;font-size:1px;line-height:1px;">&nbsp;</span>
                </th>
                <th align="left" style="padding:10px 8px 10px 4px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;">
                    Producto
                </th>
                <th align="left" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:110px;">
                    SKU
                </th>
                <th align="center" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:52px;">
                    Cant.
                </th>
                <th align="right" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:76px;">
                    Costo
                </th>
                <th align="right" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:70px;">
                    Envío
                </th>
                <th align="right" style="padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:88px;">
                    Subtotal
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                @php $image = $item->orderItem?->imageUrl(160); @endphp
                <tr>
                    <td width="{{ $thumb + 20 }}" valign="top"
                        style="padding:12px 0 12px 14px;border-top:1px solid #f3f4f6;">
                        <x-brevare.mail-thumb :src="$image" :alt="$item->orderItem?->product_name ?? 'Producto'" :size="$thumb" />
                    </td>
                    <td style="padding:12px 8px 12px 4px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;vertical-align:top;">
                        {{ $item->orderItem?->product_name ?? 'Producto' }}
                    </td>
                    <td style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:12px;color:#6b7280;">
                        {{ $item->orderItem?->supplier_sku ?? '—' }}
                    </td>
                    <td align="center" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;">
                        {{ $item->quantity }}
                    </td>
                    <td align="right" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#4b5563;white-space:nowrap;">
                        S/ {{ number_format((float) $item->unit_cost, 2) }}
                    </td>
                    <td align="right" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#4b5563;white-space:nowrap;">
                        S/ {{ number_format((float) $item->supplier_shipping_cost * (int) $item->quantity, 2) }}
                    </td>
                    <td align="right" style="padding:12px 14px;border-top:1px solid #f3f4f6;font-size:14px;font-weight:600;color:#111827;white-space:nowrap;">
                        S/ {{ number_format((float) $item->line_cost_total, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color:#f9fafb;">
                <td colspan="5" align="right" style="padding:12px 14px;border-top:1px solid #e5e7eb;font-size:13px;font-weight:600;color:#111827;">
                    Total a pagar
                </td>
                <td align="right" style="padding:12px 8px;border-top:1px solid #e5e7eb;font-size:13px;font-weight:600;color:#4b5563;white-space:nowrap;">
                    S/ {{ number_format($totalShipping, 2) }}
                </td>
                <td align="right" style="padding:12px 14px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;white-space:nowrap;">
                    S/ {{ number_format((float) $order->total_cost, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div style="margin:0 0 24px;background-color:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:16px;font-size:14px;line-height:1.7;color:#1f2937;">
        <p style="margin:0 0 8px;font-weight:600;color:#111827;">Datos de entrega del cliente</p>
        {{ $shipping['customer_name'] }}<br>
        {{ $shipping['address'] }}@if ($shipping['reference']) ({{ $shipping['reference'] }})@endif<br>
        {{ $shipping['location'] }} · {{ $shipping['phone'] }}
    </div>

    @if ($customerOrder)
        <p style="margin:0 0 24px;font-size:13px;color:#6b7280;">
            Referencia interna del pedido del cliente:
            <strong style="color:#111827;">{{ $customerOrder->order_number }}</strong>.
            Esta orden corresponde únicamente a los productos que te corresponden de ese pedido.
        </p>
    @endif

    <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">
        Por favor confirma la disponibilidad y el despacho de la orden lo antes posible.
    </p>
</x-brevare.mail-layout>
