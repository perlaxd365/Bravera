@php
    $customer = $order->customer_snapshot ?? [];
    $address = $order->address_snapshot ?? [];
    $customerName = $customer['name'] ?? $order->user?->name ?? 'Cliente';
    $customerEmail = $customer['email'] ?? $order->user?->email ?? '—';
@endphp

<x-brevare.mail-layout :title="'Nuevo pedido '.$order->order_number" heading="Nuevo pedido confirmado" accent="success"
    :preheader="'Pedido '.$order->order_number.' por S/ '.number_format((float) $order->total, 2)">
    <p>Se confirmó una compra en Brevare.</p>

    <h2>Pedido {{ $order->order_number }}</h2>
    <p><strong>Fecha:</strong> {{ $order->paid_at?->format('d/m/Y H:i') ?? $order->created_at?->format('d/m/Y H:i') }}</p>
    <p><strong>Cliente:</strong> {{ $customerName }}<br>
        <strong>Correo:</strong> {{ $customerEmail }}<br>
        <strong>Teléfono:</strong> {{ $address['phone'] ?? '—' }}</p>

    <h2>Productos</h2>
    @foreach ($order->items as $item)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
            style="width:100%;table-layout:fixed;border-bottom:1px solid #e5e7eb;margin:0 0 14px;">
            <tr>
                <td width="72" valign="top" style="width:72px;padding:12px 10px 12px 0;">
                    <x-brevare.mail-thumb :src="$item->imageUrl(96)" :alt="$item->product_name" :size="56" />
                </td>
                <td valign="top" style="padding:12px 0;word-break:break-word;font-size:14px;line-height:1.5;color:#1f2937;">
                    <strong>{{ $item->product_name }}</strong>
                    @if ($item->variant_attributes)
                        <br><small style="color:#6b7280;">{{ collect($item->variant_attributes)->filter()->map(fn ($value) => is_array($value) ? ($value['value'] ?? '') : $value)->implode(' · ') }}</small>
                    @endif
                    <br><span style="color:#6b7280;">Cantidad: {{ $item->quantity }} · Unitario: S/ {{ number_format((float) $item->unit_price, 2) }}</span>
                    <br><strong>Subtotal: S/ {{ number_format((float) $item->line_subtotal, 2) }}</strong>
                </td>
            </tr>
        </table>
    @endforeach

    <h2>Resumen del pago</h2>
    <p><strong>Método:</strong> {{ $payment?->method ?? '—' }}<br>
        <strong>Subtotal:</strong> S/ {{ number_format((float) $order->subtotal, 2) }}<br>
        <strong>Envío:</strong> S/ {{ number_format((float) $order->shipping_total, 2) }}<br>
        @if ((float) $order->discount_total > 0)
            <strong>Descuento:</strong> − S/ {{ number_format((float) $order->discount_total, 2) }}<br>
        @endif
        <strong>Total:</strong> S/ {{ number_format((float) $order->total, 2) }}</p>

    <h2>Dirección de entrega</h2>
    <p>{{ $address['full_name'] ?? $customerName }}<br>
        {{ $address['address'] ?? '—' }}<br>
        {{ $address['reference'] ?? '' }}<br>
        {{ $address['location_label'] ?? '' }}</p>

    @if ($order->notes)
        <h2>Notas del pedido</h2>
        <p>{{ $order->notes }}</p>
    @endif
</x-brevare.mail-layout>
