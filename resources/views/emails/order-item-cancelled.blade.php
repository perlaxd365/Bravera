@php
    $name = $order->customer_snapshot['name'] ?? $order->user?->name ?? '';
    $itemImage = $item?->imageUrl(160);
@endphp

<x-brevare.mail-layout :title="'Pedido '.$order->order_number.' — Actualización'"
    :heading="$wholeOrderCancelled ? 'Tu pedido fue cancelado' : 'Actualización de tu pedido'"
    :accent="$wholeOrderCancelled ? 'danger' : 'warning'"
    :preheader="$wholeOrderCancelled
        ? 'Tu pedido '.$order->order_number.' fue cancelado por completo.'
        : 'Un producto de tu pedido '.$order->order_number.' fue cancelado.'">
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $name }}</strong>,
    </p>

    @if ($wholeOrderCancelled)
        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#4b5563;">
            Lamentamos informarte que el pedido <strong style="color:#111827;">{{ $order->order_number }}</strong>
            fue cancelado en su totalidad. Si realizaste un pago, el reembolso se procesa en un plazo
            máximo de 5 días hábiles.
        </p>

        {{-- Línea de tiempo de cancelación --}}
        <div style="margin:0 0 24px;padding:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:14px;">
            <p style="margin:0;font-size:15px;line-height:1.6;color:#991b1b;">
                <strong style="color:#7f1d1d;">Pedido cancelado</strong><br>
                {{ $order->cancellation_reason ?? 'El pedido fue cancelado' }}<br>
                @if ($order->cancelled_at)
                    <span style="font-size:13px;color:#b91c1c;">Cancelado el {{ \Carbon\Carbon::parse($order->cancelled_at)->locale('es')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm') }}</span>
                @endif
            </p>
        </div>
    @else
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
            <tr>
                <td width="76" valign="top" style="padding:0;">
                    <x-brevare.mail-thumb :src="$itemImage" :alt="$item?->product_name ?? ''" :size="56" />
                </td>
                <td valign="top" style="padding:0 0 0 12px;font-size:15px;line-height:1.6;color:#4b5563;">
                    El producto <strong style="color:#111827;">{{ $item?->product_name ?? 'seleccionado' }}</strong>
                    fue cancelado de tu pedido <strong style="color:#111827;">{{ $order->order_number }}</strong>.
                    El resto de los productos de tu pedido sigue vigente.
                </td>
            </tr>
        </table>
    @endif

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
                Total del pedido
            </td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#111827;border-top:1px solid #f3f4f6;">
                S/ {{ number_format((float) $order->total, 2) }}
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#6b7280;border-top:1px solid #f3f4f6;">
                Importe cancelado
            </td>
            <td style="padding:12px 16px;font-size:13px;font-weight:600;color:#b91c1c;border-top:1px solid #f3f4f6;">
                - S/ {{ number_format($wholeOrderCancelled ? (float) $order->cancelledTotal() : (float) ($item?->line_subtotal ?? 0), 2) }}
            </td>
        </tr>
    </table>

    @if (! $wholeOrderCancelled)
        <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#6b7280;">
            Si realizaste un pago por el producto cancelado, el reembolso se procesa en un plazo máximo
            de 5 días hábiles.
        </p>
    @endif

    <p style="margin:0;text-align:center;">
        <a href="{{ route('account.orders', [], true) }}"
            style="display:inline-block;background-color:#111827;color:#ffffff;padding:12px 24px;border-radius:9999px;text-decoration:none;font-size:14px;font-weight:600;">
            Ver mis pedidos
        </a>
    </p>
</x-brevare.mail-layout>
