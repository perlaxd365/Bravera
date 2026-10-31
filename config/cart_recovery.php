<?php

return [
    'enabled' => (bool) env('CART_RECOVERY_ENABLED', true),

    'days' => max(1, (int) env('CART_RECOVERY_DAYS', 3)),

    /*
    | Se envían como máximo tres recordatorios por carrito. Así se recupera
    | la oportunidad sin convertir el correo en spam.
    */
    'max_emails' => max(1, (int) env('CART_RECOVERY_MAX_EMAILS', 3)),
];
