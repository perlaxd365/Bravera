@php
    $shipping = $order->shippingDetails();
    $customerOrder = $order->order;
    $totalShipping = round(
        $order->items->sum(fn ($item) => (float) $item->supplier_shipping_cost),
        2
    );
    $thumb = 64;
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

    @foreach ($order->items as $item)
        @php $image = $item->orderItem?->imageUrl(192); @endphp
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
            style="width:100%;margin:0 0 12px;border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;">
            <tr>
                <td width="80" valign="top" style="width:80px;padding:12px 10px 12px 12px;">
                    <x-brevare.mail-thumb :src="$image" :alt="$item->orderItem?->product_name ?? 'Producto'" :size="$thumb" />
                </td>
                <td valign="top" style="padding:12px 12px 12px 0;word-break:break-word;font-size:14px;line-height:1.5;color:#111827;">
                    <strong>{{ $item->orderItem?->product_name ?? 'Producto' }}</strong>
                    <br><span style="font-size:12px;color:#6b7280;">SKU: {{ $item->orderItem?->supplier_sku ?? '—' }} · Cantidad: {{ $item->quantity }}</span>
                    <br><span style="font-size:12px;color:#6b7280;">Costo unitario: S/ {{ number_format((float) $item->unit_cost, 2) }} · Envío asignado: S/ {{ number_format((float) $item->supplier_shipping_cost, 2) }}</span>
                    <br><strong>Subtotal: S/ {{ number_format((float) $item->line_cost_total, 2) }}</strong>
                </td>
            </tr>
        </table>
    @endforeach

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;font-size:14px;">
        <tr>
            <td style="padding:4px 0;color:#6b7280;">Envío total</td>
            <td align="right" style="padding:4px 0;color:#111827;">S/ {{ number_format($totalShipping, 2) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">Total a pagar</td>
            <td align="right" style="padding:10px 0 0;border-top:1px solid #e5e7eb;font-weight:700;color:#111827;">S/ {{ number_format((float) $order->total_cost, 2) }}</td>
        </tr>
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
