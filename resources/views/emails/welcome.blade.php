<x-mail::message>
# ¡Bienvenido a Brevare, {{ $user->name }}!

Gracias por crear tu cuenta en **Brevare**. A partir de ahora puedes:

- Explorar nuestro catálogo de productos.
- Agregar productos a tu carrito y completar tu compra.
- Hacer seguimiento de tus pedidos desde tu cuenta.

## Ya tienes 50 Brevare Puntos

Cada **S/ 1** de compra suma **1 punto**. Con **1,000 puntos** obtienes **S/ 50**
de regalo para usar en cualquier producto de Brevare.

@component('mail::button', ['url' => route('account.points')])
Ver mis puntos
@endcomponent

@component('mail::button', ['url' => route('home')])
Ir a la tienda
@endcomponent

Saludos,<br>
El equipo de **Brevare**.
</x-mail::message>
