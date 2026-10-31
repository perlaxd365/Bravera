<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppSettingsService
{
    /**
     * Configuración editable desde el panel. Las claves técnicas se mantienen
     * fuera de la interfaz para que el formulario no muestre variables de .env.
     */
    public const GROUPS = [
        'seo' => [
            'label' => 'SEO y presencia',
            'description' => 'Enlaces públicos, imagen de marca y verificación para buscadores.',
            'icon' => 'magnifying-glass',
        ],
        'cloudinary' => [
            'label' => 'Cloudinary',
            'description' => 'Credenciales para imágenes y videos de productos.',
            'icon' => 'cloud',
        ],
        'meta' => [
            'label' => 'Meta Pixel y CAPI',
            'description' => 'Seguimiento del navegador y eventos del servidor.',
            'icon' => 'chart-bar',
        ],
        'meta_ads' => [
            'label' => 'Meta Ads',
            'description' => 'Aplicación, empresa, cuenta publicitaria y página para crear anuncios desde cada producto.',
            'icon' => 'megaphone',
        ],
    ];

    public const FIELDS = [
        'seo_organization_image' => [
            'group' => 'seo',
            'label' => 'Imagen de la organización',
            'description' => 'URL pública de la imagen usada en datos estructurados.',
            'type' => 'url',
            'config' => 'seo.organization_image',
            'placeholder' => 'https://...',
        ],
        'seo_google_verification' => [
            'group' => 'seo',
            'label' => 'Código de verificación de Google',
            'description' => 'Valor de google-site-verification, sin etiquetas HTML.',
            'type' => 'text',
            'config' => 'seo.google_site_verification',
        ],
        'seo_instagram_url' => [
            'group' => 'seo',
            'label' => 'Instagram',
            'description' => 'Perfil público que aparecerá como canal oficial.',
            'type' => 'url',
            'social' => 'instagram',
            'placeholder' => 'https://instagram.com/...',
        ],
        'seo_facebook_url' => [
            'group' => 'seo',
            'label' => 'Facebook',
            'description' => 'Página oficial de Brevare.',
            'type' => 'url',
            'social' => 'facebook',
            'placeholder' => 'https://facebook.com/...',
        ],
        'seo_tiktok_url' => [
            'group' => 'seo',
            'label' => 'TikTok',
            'description' => 'Perfil público de la marca.',
            'type' => 'url',
            'social' => 'tiktok',
            'placeholder' => 'https://tiktok.com/@...',
        ],
        'seo_linkedin_url' => [
            'group' => 'seo',
            'label' => 'LinkedIn',
            'description' => 'Página corporativa, si corresponde.',
            'type' => 'url',
            'social' => 'linkedin',
            'placeholder' => 'https://linkedin.com/company/...',
        ],
        'seo_youtube_url' => [
            'group' => 'seo',
            'label' => 'YouTube',
            'description' => 'Canal oficial de la marca.',
            'type' => 'url',
            'social' => 'youtube',
            'placeholder' => 'https://youtube.com/@...',
        ],
        'cloudinary_cloud_name' => [
            'group' => 'cloudinary',
            'label' => 'Cloud name',
            'description' => 'Nombre del entorno de Cloudinary.',
            'type' => 'text',
            'config' => 'services.cloudinary.cloud_name',
        ],
        'cloudinary_api_key' => [
            'group' => 'cloudinary',
            'label' => 'API key',
            'description' => 'Clave de API. Se guarda cifrada y nunca se vuelve a mostrar completa.',
            'type' => 'password',
            'secret' => true,
            'config' => 'services.cloudinary.api_key',
        ],
        'cloudinary_api_secret' => [
            'group' => 'cloudinary',
            'label' => 'API secret',
            'description' => 'Secreto de API. Se guarda cifrado.',
            'type' => 'password',
            'secret' => true,
            'config' => 'services.cloudinary.api_secret',
        ],
        'meta_pixel_id' => [
            'group' => 'meta',
            'label' => 'Pixel ID',
            'description' => 'Identificador del dataset de Meta.',
            'type' => 'text',
            'config' => 'meta.pixel_id',
        ],
        'meta_pixel_enabled' => [
            'group' => 'meta',
            'label' => 'Activar Pixel en la tienda',
            'description' => 'Carga el Pixel después del consentimiento de marketing.',
            'type' => 'boolean',
            'config' => 'meta.pixel_enabled',
        ],
        'meta_capi_enabled' => [
            'group' => 'meta',
            'label' => 'Activar Conversions API',
            'description' => 'Envía eventos de compra desde el servidor.',
            'type' => 'boolean',
            'config' => 'meta.capi_enabled',
        ],
        'meta_capi_access_token' => [
            'group' => 'meta',
            'label' => 'Token de Conversions API',
            'description' => 'Token privado de Meta. Se guarda cifrado.',
            'type' => 'password',
            'secret' => true,
            'config' => 'meta.access_token',
        ],
        'meta_test_event_code' => [
            'group' => 'meta',
            'label' => 'Código de evento de prueba',
            'description' => 'Opcional. Úsalo solo durante una prueba de Meta.',
            'type' => 'text',
            'optional' => true,
            'config' => 'meta.test_event_code',
        ],
        'meta_domain_verification' => [
            'group' => 'meta',
            'label' => 'Verificación de dominio',
            'description' => 'Código entregado por Meta para dominios.',
            'type' => 'text',
            'config' => 'meta.domain_verification',
        ],
        'meta_ads_enabled' => [
            'group' => 'meta_ads',
            'label' => 'Activar creación de anuncios',
            'description' => 'Muestra las herramientas de Meta Ads en la ficha del producto.',
            'type' => 'boolean',
            'config' => 'meta.ads.enabled',
        ],
        'meta_app_id' => [
            'group' => 'meta_ads',
            'label' => 'ID de aplicación de Meta',
            'description' => 'Identificador de la app desde la que se generó el token publicitario.',
            'type' => 'text',
            'config' => 'meta.ads.app_id',
            'placeholder' => '123456789012345',
        ],
        'meta_business_id' => [
            'group' => 'meta_ads',
            'label' => 'ID de empresa de Meta',
            'description' => 'Identificador del portafolio comercial que administra la página y la cuenta.',
            'type' => 'text',
            'config' => 'meta.ads.business_id',
            'placeholder' => '123456789012345',
        ],
        'meta_ad_account_id' => [
            'group' => 'meta_ads',
            'label' => 'ID de cuenta publicitaria',
            'description' => 'Identificador de la cuenta de anuncios.',
            'type' => 'text',
            'config' => 'meta.ads.ad_account_id',
            'placeholder' => 'act_123456789012345',
        ],
        'meta_page_id' => [
            'group' => 'meta_ads',
            'label' => 'ID de página',
            'description' => 'Página de Facebook que firma los anuncios.',
            'type' => 'text',
            'config' => 'meta.ads.page_id',
            'placeholder' => '123456789012345',
        ],
        'meta_instagram_actor_id' => [
            'group' => 'meta_ads',
            'label' => 'ID de Instagram',
            'description' => 'ID numérico de la cuenta profesional, no el @usuario ni el Page ID.',
            'type' => 'text',
            'optional' => true,
            'config' => 'meta.ads.instagram_actor_id',
        ],
        'meta_ads_access_token' => [
            'group' => 'meta_ads',
            'label' => 'Token de Marketing API',
            'description' => 'Token de usuario del sistema. Se guarda cifrado.',
            'type' => 'password',
            'secret' => true,
            'config' => 'meta.ads.access_token',
        ],
        'meta_app_secret' => [
            'group' => 'meta_ads',
            'label' => 'App secret de Meta',
            'description' => 'Secreto de la aplicación de Meta. Se guarda cifrado.',
            'type' => 'password',
            'secret' => true,
            'optional' => true,
            'config' => 'meta.ads.app_secret',
        ],
    ];

    public function applyToConfiguration(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $stored = AppSetting::query()->get()->keyBy('key');

        foreach (self::FIELDS as $field => $definition) {
            if (! isset($definition['config'])) {
                continue;
            }

            $value = $this->decode($stored->get($field));

            if ($value === null) {
                continue;
            }

            if (($definition['type'] ?? null) === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOL);
            }

            config()->set($definition['config'], $value);
        }

        $socialLinks = [];
        $hasSocialOverrides = false;

        foreach (self::FIELDS as $field => $definition) {
            if (! isset($definition['social'])) {
                continue;
            }

            $value = $this->decode($stored->get($field));

            if ($stored->has($field)) {
                $hasSocialOverrides = true;
            }

            if (filled($value)) {
                $socialLinks[] = $value;
            }
        }

        if ($hasSocialOverrides) {
            config()->set('seo.same_as', array_values(array_unique($socialLinks)));
        }
    }

    public function displayValue(string $field): mixed
    {
        $definition = self::FIELDS[$field] ?? null;

        if (! $definition) {
            return null;
        }

        $record = AppSetting::query()->where('key', $field)->first();
        $stored = $this->decode($record);

        if ($stored !== null) {
            return $stored;
        }

        if (isset($definition['config'])) {
            return config($definition['config']);
        }

        if (isset($definition['social'])) {
            return $this->socialValue($definition['social']);
        }

        return null;
    }

    public function isConfigured(string $field): bool
    {
        if ((self::FIELDS[$field]['type'] ?? null) === 'boolean') {
            return $this->displayValue($field) !== null;
        }

        $value = $this->displayValue($field);

        return filled($value);
    }

    public function save(array $values, array $secrets = []): void
    {
        DB::transaction(function () use ($values, $secrets): void {
            foreach (self::FIELDS as $field => $definition) {
                if (($definition['type'] ?? null) === 'boolean') {
                    $value = filter_var($values[$field] ?? false, FILTER_VALIDATE_BOOL) ? '1' : '0';
                } elseif (! empty($definition['secret'])) {
                    $value = trim((string) ($secrets[$field] ?? ''));

                    if ($value === '') {
                        continue;
                    }
                } else {
                    $value = trim((string) ($values[$field] ?? ''));
                }

                AppSetting::query()->updateOrCreate(
                    ['key' => $field],
                    [
                        'value' => ! empty($definition['secret'])
                            ? Crypt::encryptString($value)
                            : $value,
                        'is_encrypted' => ! empty($definition['secret']),
                    ],
                );
            }
        });
    }

    public function summary(): array
    {
        $groups = [];

        foreach (self::GROUPS as $group => $meta) {
            $fields = array_filter(
                self::FIELDS,
                fn (array $definition): bool => $definition['group'] === $group,
            );
            $requiredFields = array_filter(
                $fields,
                fn (array $definition): bool => empty($definition['optional']),
            );
            $configured = count(array_filter(
                array_keys($requiredFields),
                fn (string $field): bool => $this->isConfigured($field),
            ));

            $groups[$group] = [
                ...$meta,
                'configured' => $configured,
                'total' => count($requiredFields),
                'ready' => $configured === count($requiredFields),
            ];
        }

        return $groups;
    }

    private function decode(?AppSetting $record): ?string
    {
        if (! $record || $record->value === null) {
            return null;
        }

        if (! $record->is_encrypted) {
            return (string) $record->value;
        }

        try {
            return Crypt::decryptString($record->value);
        } catch (DecryptException) {
            return null;
        }
    }

    private function socialValue(string $network): ?string
    {
        $record = null;

        foreach (self::FIELDS as $field => $definition) {
            if (($definition['social'] ?? null) !== $network) {
                continue;
            }

            $record = AppSetting::query()->where('key', $field)->first();
            break;
        }

        if ($record) {
            return $this->decode($record);
        }

        $legacyOrder = ['instagram', 'facebook', 'tiktok', 'linkedin', 'youtube'];
        $index = array_search($network, $legacyOrder, true);

        return $index === false
            ? null
            : (config('seo.same_as', [])[$index] ?? null);
    }
}
