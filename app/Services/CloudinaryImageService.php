<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CloudinaryImageService
{
    /**
     * Carpetas de Cloudinary por área del sistema.
     */
    public const FOLDER_PRODUCTS = 'productos';

    public const FOLDER_BRANDS = 'marcas';

    public const FOLDER_CATEGORIES = 'categorias';

    public const FOLDER_AVATARS = 'avatares';

    /**
     * Cliente de Cloudinary configurado con las credenciales del .env.
     */
    protected Cloudinary $client;

    public function __construct()
    {
        $this->client = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => config('services.cloudinary.secure', true),
            ],
        ]);
    }

    /**
     * Sube una imagen a Cloudinary dentro de la carpeta indicada.
     *
     * @return array{public_id: string, url: string, secure_url: string, file_name: string, format: ?string, size: ?int, width: ?int, height: ?int}
     */
    public function upload(UploadedFile|TemporaryUploadedFile $file, string $folder): array
    {
        $response = $this->client->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'resource_type' => 'image',
                'unique_filename' => true,
                'use_filename' => true,
            ]
        );

        return [
            'public_id' => (string) $response['public_id'],
            'url' => (string) $response['url'],
            'secure_url' => (string) $response['secure_url'],
            'file_name' => $file->getClientOriginalName(),
            'format' => $response['format'] ?? null,
            'size' => isset($response['bytes']) ? (int) $response['bytes'] : null,
            'width' => isset($response['width']) ? (int) $response['width'] : null,
            'height' => isset($response['height']) ? (int) $response['height'] : null,
        ];
    }

    /**
     * Sube un video de producto a Cloudinary.
     *
     * @return array{public_id: string, url: string, secure_url: string, file_name: string, format: ?string, size: ?int, width: ?int, height: ?int, duration: ?float}
     */
    public function uploadVideo(UploadedFile|TemporaryUploadedFile $file, string $folder): array
    {
        $response = $this->client->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'resource_type' => 'video',
                'unique_filename' => true,
                'use_filename' => true,
                // En esta versión del SDK las transformaciones de subida se indican
                // como parámetros de transformación en el nivel superior. Enviar
                // `transformation` como string hace que el SDK lo trate como una
                // acción genérica y Cloudinary rechaza `w_320` como transformación.
                'width' => 320,
                'height' => 180,
                'crop' => 'limit',
                'quality' => 'auto:low',
                'bit_rate' => '100k',
                'video_codec' => 'h264',
                'format' => 'mp4',
            ]
        );

        return [
            'public_id' => (string) $response['public_id'],
            'url' => (string) $response['url'],
            'secure_url' => (string) $response['secure_url'],
            'file_name' => $file->getClientOriginalName(),
            'format' => $response['format'] ?? null,
            'size' => isset($response['bytes']) ? (int) $response['bytes'] : null,
            'width' => isset($response['width']) ? (int) $response['width'] : null,
            'height' => isset($response['height']) ? (int) $response['height'] : null,
            'duration' => isset($response['duration']) ? (float) $response['duration'] : null,
        ];
    }

    /**
     * Elimina un recurso de Cloudinary por su public_id.
     */
    public function delete(?string $publicId, string $resourceType = 'image'): void
    {
        if (! $publicId) {
            return;
        }

        try {
            $this->client->adminApi()->deleteAssets($publicId, ['resource_type' => $resourceType]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Indica si una URL almacenada pertenece a Cloudinary.
     */
    public function isCloudinaryUrl(?string $url): bool
    {
        return $url !== null && str_contains($url, 'res.cloudinary.com');
    }

    /**
     * Obtiene el public_id desde una URL de Cloudinary.
     *
     * Convierte URLs generadas con use_filename + unique_filename:
     *
     *  https://res.cloudinary.com/<cloud>/image/upload/v1611/marcas/logo-ab12.png
     *      -> marcas/logo-ab12
     */
    public function publicIdFromUrl(?string $url): ?string
    {
        if (! $this->isCloudinaryUrl($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! $path) {
            return null;
        }

        $parts = explode('/', trim($path, '/'));

        // Busca el segmento "upload" (image/upload/, video/upload/, etc.)
        $uploadIndex = array_search('upload', $parts, true);

        if ($uploadIndex === false) {
            return null;
        }

        $publicIdParts = array_slice($parts, $uploadIndex + 1);

        // Omite la versión (v12345) si viene incluida.
        if (isset($publicIdParts[0]) && preg_match('/^v\d+$/', $publicIdParts[0])) {
            array_shift($publicIdParts);
        }

        if (empty($publicIdParts)) {
            return null;
        }

        $last = end($publicIdParts);

        if ($last !== false) {
            $publicIdParts[count($publicIdParts) - 1] = preg_replace(
                '/\.(jpg|jpeg|png|gif|webp|avif|svg|bmp|ico)$/i',
                '',
                $last
            );
        }

        $publicId = implode('/', $publicIdParts);

        return $publicId !== '' ? $publicId : null;
    }
}
