<x-brevare.mail-layout
    title="Ganaste Brevare Puntos"
    heading="Tus puntos ya están sumados"
    accent="success"
    preheader="Tu compra {{ $orderNumber }} sumó {{ $points }} Brevare Puntos. Ya tienes {{ $balance }} puntos.">

    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#4b5563;">
        Hola <strong style="color:#111827;">{{ $user->name }}</strong>,
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4b5563;">
        Tu compra <strong style="color:#111827;">{{ $orderNumber }}</strong> sumó
        <strong style="color:#047857;">{{ $points }} Brevare Puntos</strong>.
    </p>

    <div style="margin:0 0 20px;padding:20px;border:1px solid #d1fae5;border-radius:16px;background:#ecfdf5;">
        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#047857;">Saldo actual</p>
        <p style="margin:6px 0 0;font-size:30px;line-height:1.1;font-weight:800;color:#064e3b;">{{ number_format($balance, 0) }} puntos</p>
        <p style="margin:6px 0 0;font-size:13px;color:#047857;">Equivalen a S/ {{ number_format($amount, 2) }} en Brevare Puntos.</p>
    </div>

    <p style="margin:0 0 8px;font-size:14px;line-height:1.7;color:#4b5563;">
        Cada <strong>S/ 1</strong> suma <strong>1 punto</strong>. Desde <strong>1,000 puntos</strong>
        puedes canjear <strong>S/ 50</strong> para usar en cualquier producto de Brevare.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px auto 20px;">
        <tr>
            <td align="center" style="border-radius:999px;background-color:#047857;">
                <a href="{{ route('account.points') }}"
                    style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">
                    Ver mis puntos
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0;text-align:center;font-size:12px;line-height:1.6;color:#9ca3af;">
        Gracias por comprar en Brevare.
    </p>
</x-brevare.mail-layout>
