<x-mail::message>
# Verifica tu correo, {{ $user->name }}!

Usa este código para verificar de forma segura tu correo en **Brevare**:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

Este código es válido por **10 minutos**. Si no solicitaste este código, puedes ignorar este correo sin problema.

Saludos,<br>
El equipo de **Brevare**.
</x-mail::message>
