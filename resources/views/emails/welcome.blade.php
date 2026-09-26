<x-mail::message>
# ¡Bienvenido a Bravera, {{ $user->name }}!

Gracias por crear tu cuenta en **Bravera**. A partir de ahora puedes:

- Explorar nuestro catálogo de productos.
- Agregar productos a tu carrito y completar tu compra.
- Hacer seguimiento de tus pedidos desde tu cuenta.

@component('mail::button', ['url' => route('home')])
Ir a la tienda
@endcomponent

Saludos,<br>
El equipo de **Bravera**.
</x-mail::message>