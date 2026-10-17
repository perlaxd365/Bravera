<?php

namespace App\Modules\Payments\Gateways\Izipay;

/**
 * Firma kr-hash de Izipay (IPN y retorno del navegador).
 *
 * Izipay envía el resultado del pago como campos de formulario
 * (kr-answer, kr-hash, kr-hash-key, kr-hash-algorithm). El hash es el
 * HMAC-SHA256 de kr-answer, calculado con la clave de la tienda, así que
 * sin esta verificación cualquiera podría confirmar un pedido ajeno.
 *
 * La clave puede ser la HMAC del back office (kr-hash-key=sha256_hmac) o la
 * contraseña de la API REST (kr-hash-key=password); se aceptan ambas para no
 * depender de cómo esté configurada la tienda, y el hash se compara siempre
 * en tiempo constante.
 */
class IzipaySignature
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function verify(array $payload): bool
    {
        $answer = $payload['kr-answer'] ?? null;
        $hash = $payload['kr-hash'] ?? null;
        $algorithm = (string) ($payload['kr-hash-algorithm'] ?? 'sha256_hmac');
        $keyKind = (string) ($payload['kr-hash-key'] ?? 'sha256_hmac');

        if (! is_string($answer) || ! is_string($hash) || $answer === '' || $hash === '') {
            return false;
        }

        if ($algorithm !== '' && $algorithm !== 'sha256_hmac') {
            return false;
        }

        if ($keyKind !== '' && $keyKind !== 'sha256_hmac' && $keyKind !== 'password') {
            return false;
        }

        $normalized = self::normalize($answer);

        foreach (self::candidateKeys() as $candidate) {
            if (hash_equals(hash_hmac('sha256', $normalized, $candidate), $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Izipay calcula el hash sobre el JSON con las barras sin escapar, tal como
     * publica su ejemplo oficial en PHP.
     */
    public static function normalize(string $answer): string
    {
        return str_replace('\/', '/', $answer);
    }

    /**
     * Claves con las que se intenta validar, en orden de preferencia.
     *
     * @return list<string>
     */
    public static function candidateKeys(): array
    {
        $keys = [
            (string) config('payments.izipay.hmac_key', ''),
            (string) config('payments.izipay.rest_password', ''),
        ];

        $keys = array_values(array_unique(array_filter(
            array_map('trim', $keys),
            static fn (string $key): bool => $key !== '',
        )));

        return $keys;
    }
}
