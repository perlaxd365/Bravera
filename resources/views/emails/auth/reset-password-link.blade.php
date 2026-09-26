<x-mail::message>
# Restablece tu contraseña, {{ $user->name }}!

Recibimos una solicitud para restablecer la contraseña de tu cuenta en **Bravera**.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Crear nueva contraseña
@endcomponent

Este enlace expira en **60 minutos**. Si no solicitaste este cambio, ignora este correo y tu contraseña seguirá igual.

Saludos,<br>
El equipo de **Bravera**.
</x-mail::message>