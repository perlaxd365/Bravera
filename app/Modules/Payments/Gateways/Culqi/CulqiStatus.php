<?php

namespace App\Modules\Payments\Gateways\Culqi;

/**
 * Resuelve si un objeto de Culqi (cargo u orden) representa dinero cobrado.
 *
 * Culqi usa dos formas distintas y no es uniforme. Ambas verificadas contra la
 * API real en modo test:
 *
 *  - Cargo (POST /v2/charges): no trae campo `status`. El resultado está en
 *    `outcome.code` ("AUT0000" = aprobada) y `outcome.type`
 *    ("venta_exutosa"). Sí trae `paid`, que vale true en producción y false en
 *    el sandbox, así que nunca se usa como único criterio.
 *
 *  - Orden (POST /v2/orders, Yape / PagoEfectivo): trae `state` ("pending"), no
 *    `outcome`, y la fecha de cobro en `paid_at` (null mientras no se paga).
 *    La URL de pago del cliente está en `url_pe`, no en `payment_url`.
 *
 * Concentrarlo aquí evita que el gateway y el webhook interpreten la misma
 * respuesta de forma distinta, que es como un pago se marca cobrado en un sitio
 * y pendiente en el otro.
 */
final class CulqiStatus
{
    /**
     * Código de autorización de Culqi para una venta aprobada.
     */
    public const APPROVED_CODE = 'AUT0000';

    public const PAID = 'paid';

    public const PENDING = 'pending';

    public const FAILED = 'failed';

    public const UNKNOWN = 'unknown';

    private const SUCCESS_OUTCOMES = [
        'venta_exitosa',
        'venta_exitosa_capturada',
        'confirmada',
    ];

    private const PENDING_OUTCOMES = [
        'venta_pendiente',
        'pendiente',
        'authorized',
    ];

    /**
     * Fragmentos con los que Culqi responde cuando el problema es del comercio
     * y no del comprador: un BIN que no conoce, un emisor que no soporta. En
     * ese caso el user_message solo dice "contactate con soporte", que deja al
     * cliente sin ninguna acción posible y lo invites a escribirle al comercio.
     *
     * Verificado contra la API real (modo test): ante un `card_number` con un
     * BIN desconocido Culqi responde 400 con user_message "Contactáte con
     * soporte" y merchant_message "No se encuentra el Bin de la tarjeta".
     */
    private const UNACTIONABLE_FRAGMENTS = [
        'soporte',
        'culqi.com',
        'contacta',
        'contactarse',
        'contácta',
        'ponerse en contacto',
    ];

    private const PAID_STATES = ['paid', 'pagado', 'completada', 'paid_in_process'];

    private const PENDING_STATES = ['pending', 'pendiente', 'paid_in_process'];

    /**
     * @param  array<string, mixed>  $object
     * @return self::PAID|self::PENDING|self::FAILED|self::UNKNOWN
     */
    public static function resolve(array $object): string
    {
        return self::isOrder($object)
            ? self::resolveOrder($object)
            : self::resolveCharge($object);
    }

    /**
     * Una orden trae `object: "order"`. Se acepta también el campo `state` por
     * si Culqi lo devuelve sin el discriminante.
     *
     * @param  array<string, mixed>  $object
     */
    private static function isOrder(array $object): bool
    {
        return ($object['object'] ?? null) === 'order' || array_key_exists('state', $object);
    }

    /**
     * @param  array<string, mixed>  $order
     * @return self::PAID|self::PENDING|self::FAILED|self::UNKNOWN
     */
    private static function resolveOrder(array $order): string
    {
        // `paid_at` es la señal más fiable: Culqi lo deja en null hasta que el
        // pago se concreta, y no depende de saber el texto exacto de `state`.
        if (! empty($order['paid_at'])) {
            return self::PAID;
        }

        $state = strtolower(trim((string) ($order['state'] ?? '')));

        if ($state === '') {
            return self::UNKNOWN;
        }

        if (in_array($state, self::PAID_STATES, true)) {
            return self::PAID;
        }

        if (in_array($state, self::PENDING_STATES, true)) {
            return self::PENDING;
        }

        return self::FAILED;
    }

    /**
     * @param  array<string, mixed>  $charge
     * @return self::PAID|self::PENDING|self::FAILED|self::UNKNOWN
     */
    private static function resolveCharge(array $charge): string
    {
        $outcome = (array) ($charge['outcome'] ?? []);
        $code = (string) ($outcome['code'] ?? '');
        $type = strtolower((string) ($outcome['type'] ?? ''));

        if ($code === self::APPROVED_CODE
            || ($charge['paid'] ?? false) === true
            || in_array($type, self::SUCCESS_OUTCOMES, true)) {
            return self::PAID;
        }

        if ($code === '' && $type === '') {
            return self::UNKNOWN;
        }

        if (in_array($type, self::PENDING_OUTCOMES, true)) {
            return self::PENDING;
        }

        return self::FAILED;
    }

    /**
     * Mensaje de Culqi apto para mostrar al comprador.
     *
     * Devuelve null cuando el texto no es accionable, para que quien lo use
     * recurra a su propio mensaje. El detalle real queda disponible con
     * merchantMessage(), que el gateway registra en el log.
     *
     * @param  array<string, mixed>  $object
     */
    public static function userMessage(array $object): ?string
    {
        $message = $object['outcome']['user_message'] ?? null;

        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        $message = trim($message);

        return self::isUnactionable($message) ? null : $message;
    }

    /**
     * ¿El texto manda al cliente a soporte en vez de decirle qué hacer?
     */
    private static function isUnactionable(string $message): bool
    {
        $normalized = mb_strtolower($message);

        foreach (self::UNACTIONABLE_FRAGMENTS as $fragment) {
            if (str_contains($normalized, mb_strtolower($fragment))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $object
     */
    public static function merchantMessage(array $object): ?string
    {
        $message = $object['outcome']['merchant_message'] ?? null;

        return is_string($message) && trim($message) !== '' ? $message : null;
    }
}
