<x-brevare.mail-layout :title="'Reclamo '.$claim->code" heading="Recibimos tu reclamo" accent="primary">
    <p>Hola {{ $claim->name }},</p>

    <p>Registramos tu {{ $claim->claim_type }}. Guarda este código para cualquier consulta sobre el caso:</p>

    <p style="font-size: 20px; font-weight: 700; letter-spacing: 1px;">{{ $claim->code }}</p>

    <h2>Detalle registrado</h2>
    <p><strong>Bien o servicio:</strong> {{ $claim->claimed_good }}</p>
    @if ($claim->order_number)
        <p><strong>Número de pedido:</strong> {{ $claim->order_number }}</p>
    @endif
    <p><strong>Descripción:</strong><br>{{ $claim->description }}</p>
    <p><strong>Pedido concreto:</strong><br>{{ $claim->request }}</p>
    <p><strong>Fecha de registro:</strong> {{ $claim->created_at?->format('d/m/Y H:i') }}</p>

    <p>Este correo confirma la recepción de tu caso. Si necesitas contactarnos, responde a este mensaje o escribe a
        <a href="mailto:administracion@brevare.com">administracion@brevare.com</a>.</p>
</x-brevare.mail-layout>
