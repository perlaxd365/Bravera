@php
    $copy = match ($newStatus->value) {
        'processing' => [
            'accent' => 'primary',
            'heading' => 'Estamos preparando tu pedido',
            'body' => 'Ya estamos armando tu pedido. Te avisaremos en cuanto salga hacia tu dirección.',
        ],
        'shipped' => [
            'accent' => 'info',
            'heading' => 'Tu pedido fue despachado',
            'body' => 'Tu pedido ya salió y está en camino a la dirección registrada.',
        ],
        'delivered' => [
            'accent' => 'success',
            'heading' => 'Tu pedido fue entregado',
            'body' => 'Esperamos que disfrutes tu pedido. ¡Gracias por comprar en Brevare!',
        ],
        'cancelled' => [
            'accent' => 'danger',
            'heading' => 'Tu pedido fue cancelado',
            'body' => 'Lamentamos informarte que el pedido fue cancelado. Si realizaste un pago, el reembolso se processes en un plazo máximo de 5 días hábiles.',
        ],
        'refunded' => [
            'accent' => 'neutral',
            'heading' => 'Tu pedido fue reembolsado',
            'body' => 'El reembolso de tu pedido fue procesado. El dinero regresa a tu medio de pago original.',
        ],
        'confirmed' => [
            'accent' => 'success',
            'heading' => 'Tu pedido está confirmado',
            'body' => 'Confirmamos la recepción de tu pedido y ya estamos coordinando el despacho.',
        ],
        default => [
            'accent' => 'primary',
            'heading' => 'Tu pedido cambió de estado',
            'body' => 'Te informamos que el estado de tu pedido fue actualizado.',
        ],
    };

    $thumb = 56;
@endphp

<x-brevare.mail-layout :title="'Pedido '.$order->order_number" :heading="$copy['heading']" :accent="$copy['accent']"
    :preheader="'Tu pedido '.$order->order_number.' ahora está en: '.$newStatus->label().'.'">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $order->customer_snapshot['name'] ?? $order->user?->name ?? '' }}</strong>,
    </p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#4b5563;">
        {{ $copy['body'] }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;">
        <tr>
            <td style="background-color:#f9fafb;padding:12px 16px;font-size:13px;color:#6b7280;width:45%;">
                Pedido
            </td>
            <td style="background-color:#f9fafb;padding:12px 16px;font-size:13px;font-weight:600;color:#111827;">
                {{ $order->order_number }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">
                Estado anterior
            </td>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">
                {{ $previousStatus->label() }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">
                Estado actual
            </td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#111827;border-top:1px solid #f3f4f6;">
                {{ $newStatus->label() }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">
                Total
            </td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#111827;border-top:1px solid #f3f4f6;">
                S/ {{ number_format((float) $order->total, 2) }}
            </td>
        </tr>
    </table>

    @if ($newStatus->value === 'shipped' || $newStatus->value === 'delivered')
        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
            <strong style="color:#111827;">Dirección de entrega:</strong><br>
            {{ $order->address_snapshot['full_name'] ?? '' }}<br>
            {{ $order->address_snapshot['address'] ?? '' }}<br>
            {{ $order->address_snapshot['location_label'] ?? '' }}<br>
            {{ $order->address_snapshot['phone'] ?? '' }}
        </p>
    @endif

    @php $activeItems = $order->items->filter->isActive()->values(); @endphp

    @if ($activeItems->isNotEmpty() && in_array($newStatus->value, ['confirmed', 'processing', 'shipped', 'delivered'], true))
        <h2 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#111827;">
            Productos del pedido ({{ $activeItems->count() }})
        </h2>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
            style="margin:0 0 24px;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;border-collapse:separate;">
            <thead>
                <tr style="background-color:#f9fafb;">
                    <th width="{{ $thumb + 20 }}" style="padding:0;font-size:11px;line-height:1px;">
                        <span style="display:none;font-size:1px;line-height:1px;">&nbsp;</span>
                    </th>
                    <th align="left" style="padding:10px 4px 10px 14px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;">
                        Producto
                    </th>
                    <th align="center" style="padding:10px 8px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:52px;">
                        Cant.
                    </th>
                    <th align="right" style="padding:10px 14px;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#6b7280;width:92px;">
                        Subtotal
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activeItems as $item)
                    @php $image = $item->imageUrl(160); @endphp
                    <tr>
                        <td width="{{ $thumb + 20 }}" valign="top"
                            style="padding:12px 0 12px 14px;border-top:1px solid #f3f4f6;">
                            <x-brevare.mail-thumb :src="$image" :alt="$item->product_name" :size="$thumb" />
                        </td>
                        <td style="padding:12px 14px 12px 4px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;vertical-align:top;">
                            {{ $item->product_name }}
                            @if ($item->variant_attributes)
                                <br>
                                <span style="font-size:12px;color:#6b7280;">
                                    {{ collect($item->variant_attributes)->filter()->map(fn ($v) => is_array($v) ? ($v['value'] ?? '') : $v)->implode(' · ') }}
                                </span>
                            @endif
                            @if ($item->supplier_name)
                                <br>
                                <span style="font-size:11px;color:#9ca3af;">Proveedor: {{ $item->supplier_name }}</span>
                            @endif
                        </td>
                        <td align="center" style="padding:12px 8px;border-top:1px solid #f3f4f6;font-size:14px;color:#111827;">
                            {{ $item->quantity }}
                        </td>
                        <td align="right" style="padding:12px 14px;border-top:1px solid #f3f4f6;font-size:14px;font-weight:600;color:#111827;white-space:nowrap;">
                            S/ {{ number_format((float) $item->line_subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin:0;text-align:center;">
        <a href="{{ route('account.orders', [], true) }}"
            style="display:inline-block;background-color:#111827;color:#ffffff;padding:12px 24px;border-radius:9999px;text-decoration:none;font-size:14px;font-weight:600;">
            Ver mis pedidos
        </a>
    </p>
</x-brevare.mail-layout>
