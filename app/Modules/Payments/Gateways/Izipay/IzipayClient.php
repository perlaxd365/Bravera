<?php

namespace App\Modules\Payments\Gateways\Izipay;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP de la API REST V4 de Izipay (Mi Cuenta Web).
 *
 * Solo cubre lo que la tienda necesita: pedir el formToken con el que el
 * navegador abre el SmartForm. La autenticación es Basic con el usuario
 * (número de comercio) y la contraseña de la API REST.
 */
class IzipayClient
{
    public function __construct(
        protected string $shopId,
        protected string $password,
        protected string $endpoint,
        protected int $timeout = 20,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            shopId: trim((string) config('payments.izipay.shop_id', '')),
            password: trim((string) config('payments.izipay.rest_password', '')),
            endpoint: rtrim((string) config('payments.izipay.endpoint', 'https://api.micuentaweb.pe'), '/'),
            timeout: max(1, (int) config('payments.izipay.timeout', 20)),
        );
    }

    /**
     * Crea una orden de pago y devuelve la respuesta completa de Izipay.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws IzipayApiException
     */
    public function createPayment(array $payload): array
    {
        $response = Http::withBasicAuth($this->shopId, $this->password)
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson()
            ->post($this->endpoint.'/api-payment/V4/Charge/CreatePayment', $payload);

        return $this->decode($response, 'Charge/CreatePayment');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws IzipayApiException
     */
    protected function decode(Response $response, string $webService): array
    {
        $body = $response->json();
        $body = is_array($body) ? $body : [];

        if ($response->failed()) {
            throw new IzipayApiException(
                "Izipay rechazó la llamada a {$webService} (HTTP {$response->status()}).",
                $response->status(),
                $body,
            );
        }

        if (($body['status'] ?? null) !== 'SUCCESS') {
            throw new IzipayApiException(
                "Izipay no confirmó la llamada a {$webService}.",
                $response->status(),
                $body,
            );
        }

        return $body;
    }
}
