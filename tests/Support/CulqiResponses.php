<?php

namespace Tests\Support;

/**
 * Respuestas reales de la API de Culqi en modo test.
 *
 * Estos payloads se copiaron literalmente de llamadas a
 * secure.culqi.com / api.culqi.com con llaves pk_test_ / sk_test_. Se usan en
 * lugar de inventar formas porque la API tiene dos Particularidades que
 * rompen las suposiciones habituales:
 *
 *  - Los objetos vienen en la RAÍZ, sin envoltorio `data`.
 *  - Un cargo NO tiene campo `status`: el resultado está en `outcome.code`
 *    ("AUT0000" = aprobada) y `outcome.type` ("venta_exitosa").
 */
final class CulqiResponses
{
    /**
     * Cargo aprobado (POST /charges → 201).
     *
     * @return array<string, mixed>
     */
    public static function approvedCharge(string $id = 'chr_test_TLdqSCUFh6mh1KMD', int $amount = 5100): array
    {
        return [
            'object' => 'charge',
            'id' => $id,
            'creation_date' => 1790468027441,
            'amount' => $amount,
            'amount_refunded' => 0,
            'current_amount' => $amount,
            'currency_code' => 'PEN',
            'email' => 'comprador@brevare.test',
            'source' => [
                'object' => 'token',
                'id' => 'tkn_test_TjKR1Ezk3OKz2KCw',
                'type' => 'card',
                'last_four' => '1111',
            ],
            'outcome' => [
                'type' => 'venta_exitosa',
                'code' => 'AUT0000',
                'merchant_message' => 'La operacion de venta ha sido autorizada exitosamente',
                'user_message' => 'Su compra ha sido exitosa',
            ],
            'fraud_score' => 0.5,
            'capture' => true,
            'capture_date' => 1790468027441,
            'reference_code' => '700000038176',
            'authorization_code' => '046126',
            'duplicated' => false,
            'total_fee' => 178,
            'net_amount' => 4922,
            'paid' => false,
            'statement_descriptor' => 'Brevare',
        ];
    }

    /**
     * Cargo rechazado. `paid` false y `outcome` distinto de AUT0000.
     *
     * @return array<string, mixed>
     */
    public static function declinedCharge(string $id = 'chr_test_declined'): array
    {
        return [
            'object' => 'charge',
            'id' => $id,
            'amount' => 5100,
            'currency_code' => 'PEN',
            'outcome' => [
                'type' => 'venta_rechazada',
                'code' => 'DEC0050',
                'merchant_message' => 'Tarjeta declinada por el emisor',
                'user_message' => 'Tu banco no aprobo el pago',
            ],
            'capture' => false,
            'paid' => false,
        ];
    }

    /**
     * Cargo cuyo outcome no reconocemos. Debe quedar PENDING, nunca pagado.
     *
     * @return array<string, mixed>
     */
    public static function unknownOutcomeCharge(string $id = 'chr_test_raro'): array
    {
        return [
            'object' => 'charge',
            'id' => $id,
            'amount' => 5100,
            'outcome' => [
                'type' => 'resultado_no_catalogado',
                'code' => 'XYZ9999',
            ],
            'capture' => true,
            'paid' => false,
        ];
    }

    /**
     * Cargo sin bloque outcome (respuesta inesperada de la pasarela).
     *
     * @return array<string, mixed>
     */
    public static function chargeWithoutOutcome(string $id = 'chr_test_sin_outcome'): array
    {
        return [
            'object' => 'charge',
            'id' => $id,
            'amount' => 5100,
            'capture' => true,
            'paid' => false,
        ];
    }

    /**
     * Error de la API (HTTP 4xx). `user_message` va al comprador,
     * `merchant_message` nunca.
     *
     * @return array<string, mixed>
     */
    public static function apiError(
        string $type = 'parameter_error',
        string $param = 'card_number',
    ): array {
        return [
            'object' => 'error',
            'type' => $type,
            'merchant_message' => 'No se encuentra el Bin de la tarjeta, contactarse con Culqi',
            'user_message' => 'Revisa los datos de tu tarjeta',
            'param' => $param,
        ];
    }

    /**
     * Orden de PagoEfectivo / billetera (POST /orders → 201).
     *
     * Verificado contra la API real: el estado va en `state`, no en `status`,
     * y la URL de pago del cliente es `url_pe`. `payment_url` no existe en este
     * endpoint. `paid_at` queda en null mientras no se paga.
     *
     * @return array<string, mixed>
     */
    public static function pendingOrder(string $id = 'ord_test_bHxt0ggE2b9nYa3H'): array
    {
        return [
            'object' => 'order',
            'id' => $id,
            'amount' => 5100,
            'payment_code' => '213803274',
            'currency_code' => 'PEN',
            'description' => 'Pago del pedido',
            'order_number' => 'PRUEBA',
            'state' => 'pending',
            'total_fee' => null,
            'net_amount' => null,
            'creation_date' => 1790468816,
            'expiration_date' => 1790470615,
            'updated_at' => 1790468816,
            'paid_at' => null,
            'available_on' => null,
            'qr' => 'https://niubizqr.pagoefectivo.pe/C0452680-4DDC-4B95-9BAF-3CAEA46F08B3.png',
            'cuotealo' => null,
            'url_pe' => 'https://pre1a.payment.pagoefectivo.pe/SG3R938W-0DNKYQ57-8OODJL5J-95CDTSPK-PVGK.html',
        ];
    }

    /**
     * Orden pagada: `paid_at` informado y `state` en "paid".
     *
     * @return array<string, mixed>
     */
    public static function paidOrder(string $id = 'ord_test_pagada'): array
    {
        // array_replace, no `+`: con `+` la clave `state` de la izquierda
        // ganaría y la orden seguiría pareciendo pendiente.
        return array_replace(self::pendingOrder($id), [
            'state' => 'paid',
            'paid_at' => 1790471615,
        ]);
    }

    /**
     * Reembolso (POST /refunds → 201). Verificado: `charge_id` en singular y
     * `status: "completa"`, no "refunded".
     *
     * @return array<string, mixed>
     */
    public static function completedRefund(
        string $id = 'ref_test_HmMIaPxAsKYlK1dt',
        int $amount = 5100,
    ): array {
        return [
            'object' => 'refund',
            'id' => $id,
            'charge_id' => 'chr_test_TLdqSCUFh6mh1KMD',
            'creation_date' => 1790468692676,
            'amount' => $amount,
            'reason' => 'Devolución solicitada por el comercio',
            'status' => 'completa',
            'last_modified' => 1790468692676,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function token(string $id = 'tkn_test_YbMJERyO0SEcGn9b'): array
    {
        return [
            'object' => 'token',
            'id' => $id,
            'type' => 'card',
            'email' => 'comprador@brevare.test',
            'card_number' => '411111******1111',
            'last_four' => '1111',
            'active' => true,
        ];
    }
}
