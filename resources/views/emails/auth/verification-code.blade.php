<x-mail::message>
# Verifica tu correo, {{ $user->name }}!

Gracias por crear tu cuenta en **Brevare**. Para activar tu cuenta ingresa el siguiente código de verificación:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Este código es válido por **10 minutos**. Si no solicitaste este código, puedes ignorar este correo sin problema.

Saludos,<br>
El equipo de **Brevare**.
</x-mail::message>