@php
    $statusLabel = match ($redemption->status) {
        'paid' => 'Depositado',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        default => 'Pendiente',
    };
    $accent = match ($redemption->status) {
        'paid' => 'success',
        'rejected' => 'danger',
        default => 'info',
    };
@endphp

<x-brevare.mail-layout
    title="Actualización de tu canje"
    heading="Estado de tu canje: {{ $statusLabel }}"
    :accent="$accent"
    preheader="Tu solicitud de canje #{{ $redemption->id }} está {{ strtolower($statusLabel) }}.">

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $user->name }}</strong>,
    </p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#4b5563;">
        Tu solicitud de canje <strong>#{{ $redemption->id }}</strong> por
        <strong>{{ number_format($redemption->points) }} puntos</strong>
        (S/ {{ number_format((float) $redemption->amount, 2) }}) está en estado
        <strong>{{ $statusLabel }}</strong>.
    </p>

    @if ($redemption->payout_reference)
        <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#4b5563;">
            Referencia del depósito: <strong>{{ $redemption->payout_reference }}</strong>
        </p>
    @endif

    @if ($redemption->admin_notes)
        <div style="margin:0 0 20px;padding:16px;border:1px solid #e5e7eb;border-radius:14px;background:#f9fafb;">
            <p style="margin:0;font-size:13px;line-height:1.7;color:#4b5563;">{{ $redemption->admin_notes }}</p>
        </div>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px auto 20px;">
        <tr>
            <td align="center" style="border-radius:999px;background-color:#111827;">
                <a href="{{ route('account.points') }}"
                    style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">
                    Ver mis puntos
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;text-align:center;font-size:12px;line-height:1.6;color:#9ca3af;">
        ¿Tienes dudas? Escríbenos a {{ $company['support_email'] }}.
    </p>
</x-brevare.mail-layout>
