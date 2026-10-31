<x-mail::message>
# Tu compra está confirmada, {{ $user->name }}

Recibimos el pago del pedido **{{ $order->order_number }}** y creamos tu cuenta de Brevare.

Puedes ingresar con:

- **Correo:** {{ $user->email }}
- **Contraseña temporal:** {{ $temporaryPassword }}

@component('mail::button', ['url' => route('login'), 'color' => 'primary'])
Iniciar sesión
@endcomponent

Por seguridad, cambia esta contraseña después de ingresar.

Saludos,<br>
El equipo de **Brevare**.
</x-mail::message>
