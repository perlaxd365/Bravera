<?php

namespace App\Modules\Payments\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando no hay una pasarela de pago válida configurada, o cuando la
 * pasarela elegida no puede operar en el entorno actual.
 *
 * Falla de forma cerrada: es preferible rechazar el cobro antes que aceptar un
 * pedido sin haber cobrado nada.
 */
class PaymentGatewayNotConfiguredException extends RuntimeException {}
