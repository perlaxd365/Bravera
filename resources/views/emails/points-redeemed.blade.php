<x-brevare.mail-layout
    title="Solicitud de canje recibida"
    heading="Estamos validando tu canje"
    accent="info"
    preheader="Recibimos tu solicitud para canjear {{ number_format($redemption->points) }} puntos por S/ {{ number_format((float) $redemption->amount, 2) }}.">

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $user->name }}</strong>,
    </p>
    <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#4b5563;">
        Recibimos tu solicitud de canje. Nuestro equipo la verificará y coordinará el depósito
        de <strong>Brevare Puntos</strong> antes de completarlo.
    </p>

    <div style="margin:0 0 20px;padding:20px;border:1px solid #e5e7eb;border-radius:16px;background:#f9fafb;">
        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6b7280;">Solicitud #{{ $redemption->id }}</p>
        <p style="margin:8px 0 0;font-size:24px;line-height:1.2;font-weight:800;color:#111827;">{{ number_format($redemption->points) }} puntos</p>
        <p style="margin:4px 0 0;font-size:14px;color:#047857;">Equivalen a S/ {{ number_format((float) $redemption->amount, 2) }}</p>
        <p style="margin:8px 0 0;font-size:13px;color:#6b7280;">Estado: pendiente de validación</p>
    </div>

    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;">
        Te avisaremos por correo cuando el canje sea aprobado o depositado.
    </p>

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
