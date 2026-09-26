<?php

namespace App\Listeners;

use App\Events\CustomerRegistered;
use App\Mail\WelcomeMail;
use App\Services\ReliableMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Envía el correo de bienvenida al registrarse un cliente en la tienda.
     *
     * Un fallo del SMTP (p. ej. límite de envíos por segundo) no debe impedir
     * que el cliente se registre. El error se registra y se continúa.
     */
    public function handle(CustomerRegistered $event): void
    {
        ReliableMailer::send(
            [$event->user->email],
            new WelcomeMail($event->user),
            description: 'correo de bienvenida',
            context: ['user_id' => $event->user->id],
        );
    }
}
