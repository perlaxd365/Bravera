<?php

namespace App\Modules\Payments\Gateways\Culqi;

use App\Enums\RefundReason;
use App\Modules\Payments\Exceptions\CulqiApiException;
use App\Modules\Payments\Exceptions\PaymentGatewayNotConfiguredException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente HTTP de la API de Culqi.
 *
 * Dos hosts con claves distintas:
 *  - secure.culqi.com: tokenización, requiere la clave PÚBLICA (pk_).
 *  - api.culqi.com:    charges, órdenes y reembolsos, requiere la SECRETA (sk_).
 *
 * Los datos de la tarjeta nunca deben llegar aquí: el navegador tokeniza con
 * el Tokens API de Culqi contra el host seguro y aquí solo llega el token (tkn_...).
 *
 * Formato de respuesta: verificado contra la API real en modo test. Culqi
 * devuelve el objeto en la RAÍZ, sin envoltorio `data`, y un cargo NO trae
 * campo `status`: el resultado vive en `outcome.type` y `outcome.code`.
 */
class CulqiClient
{
    public function publicKey(): string
    {
        return $this->credential('public_key', 'CULQI_PUBLIC_KEY');
    }

    public function secretKey(): string
    {
        return $this->credential('secret_key', 'CULQI_SECRET_KEY');
    }

    /**
     * Crea un token de tarjeta. Solo se usa desde el backend cuando el
     * comercio no puede usar el navegador; en el flujo normal el token lo genera
     * el navegador.
     *
     * @param  array{card_number: string, cvv: string, expiration_month: int|string, expiration_year: int|string, email: string, card_holder_name?: string}  $card
     */
    public function createCardToken(array $card): string
    {
        $json = $this->secure()->post('/tokens', $card);

        return $this->extractId($json, 'tkn_', 'token de tarjeta');
    }

    /**
     * Crea un token de Yape a partir del celular del cliente.
     */
    public function createYapeToken(string $phoneNumber, string $email, string $documentNumber = ''): string
    {
        $payload = [
            'phone_number' => $phoneNumber,
            'email' => $email,
        ];

        if ($documentNumber !== '') {
            $payload['document_number'] = $documentNumber;
        }

        $json = $this->secure()->post('/tokens/yape', $payload);

        return $this->extractId($json, 'ype_', 'token de Yape');
    }

    /**
     * Crea un cargo. Los montos son enteros en céntimos: 10000 = S/ 100.00.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createCharge(array $payload): array
    {
        return $this->json($this->api()->post('/charges', $payload));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCharge(string $chargeId): ?array
    {
        $response = $this->api()->get('/charges/'.$chargeId);

        if ($response->status() === 404) {
            return null;
        }

        return $this->json($response);
    }

    /**
     * Crea una orden de pago (flujo Yape / QR). El cliente debe visitar
     * `payment_url` de la respuesta (viene en la raíz, sin envoltorio `data`).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload): array
    {
        return $this->json($this->api()->post('/orders', $payload));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getOrder(string $orderId): ?array
    {
        $response = $this->api()->get('/orders/'.$orderId);

        if ($response->status() === 404) {
            return null;
        }

        return $this->json($response);
    }

    /**
     * Reembolsa total o parcialmente un cargo.
     *
     * Verificado contra la API real (modo test): el endpoint es POST /refunds,
     * la clave del cargo es `charge_id` en singular y `reason` es obligatorio
     * con uno de estos valores: duplicado, fraudulento, solicitud_comprador.
     * Omitirlos devuelve 401/400 parameter_error.
     *
     * @return array<string, mixed>
     */
    public function refund(string $chargeId, ?int $amountInCents = null, RefundReason|string $reason = RefundReason::SolicitudComprador): array
    {
        $payload = [
            'charge_id' => $chargeId,
            'reason' => $reason instanceof RefundReason ? $reason->value : $reason,
        ];

        if ($amountInCents !== null) {
            $payload['amount'] = $amountInCents;
        }

        return $this->json($this->api()->post('/refunds', $payload));
    }

    private function secure(): PendingRequest
    {
        return $this->request((string) config('payments.culqi.secure_url'), $this->publicKey());
    }

    private function api(): PendingRequest
    {
        return $this->request((string) config('payments.culqi.api_url'), $this->secretKey());
    }

    private function request(string $baseUrl, string $key): PendingRequest
    {
        return Http::withToken($key)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('payments.culqi.timeout', 20))
            ->baseUrl(rtrim($baseUrl, '/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        $payload = $response->json();

        if ($response->failed()) {
            throw new CulqiApiException(
                $this->describe($payload, $response->status()),
                $response->status(),
                is_array($payload) ? $payload : ['body' => $response->body()],
            );
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractId(Response $response, string $prefix, string $what): string
    {
        $json = $this->json($response);
        $id = $json['id'] ?? null;

        if (! is_string($id) || $id === '' || ! str_starts_with($id, $prefix)) {
            throw new RuntimeException(sprintf(
                'Culqi respondió %d sin un %s válido: %s',
                $response->status(),
                $what,
                $this->describe($json, $response->status()),
            ));
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function describe(?array $payload, int $status): string
    {
        $merchant = $payload['merchant_message'] ?? null;
        $user = $payload['user_message'] ?? null;
        $type = $payload['type'] ?? null;

        return sprintf(
            'HTTP %d%s%s%s',
            $status,
            is_string($type) ? " [{$type}]" : '',
            is_string($merchant) ? " - {$merchant}" : '',
            is_string($user) ? " (usuario: {$user})" : '',
        );
    }

    private function credential(string $key, string $env): string
    {
        $value = config('payments.culqi.'.$key);

        if (! is_string($value) || trim($value) === '') {
            throw new PaymentGatewayNotConfiguredException(
                "Falta la credencial de Culqi [{$env}] en el entorno."
            );
        }

        return $value;
    }
}
