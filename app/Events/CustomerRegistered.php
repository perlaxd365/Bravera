<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento propio del registro de clientes en la tienda.
 *
 * Reemplaza a Illuminate\Auth\Events\Registered para controlar
 * la verificación por código sin disparar la verificación por
 * enlace síncrona del framework (que rompía el registro cuando
 * el SMTP fallaba).
 */
class CustomerRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public User $user) {}
}
