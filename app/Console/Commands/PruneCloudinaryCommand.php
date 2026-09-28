<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\CloudinaryImageService;
use Illuminate\Console\Command;

class PruneCloudinaryCommand extends Command
{
    /**
     * Nombre del comando.
     */
    protected $signature = 'brevare:prune-cloudinary {--days=30 : Productos en papelera con más de X días}';

    /**
     * Descripción.
     */
    protected $description = 'Elimina de Cloudinary las imágenes de productos eliminados definitivamente';

    /**
     * Ejecuta la limpieza.
     */
    public function handle(CloudinaryImageService $cloudinary): int
    {
        $days = (int) $this->option('days');

        $products = Product::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays($days))
            ->with([
                'variants' => function ($query) {
                    $query->withTrashed()->with([
                        'images' => fn ($images) => $images->withTrashed(),
                    ]);
                },
            ])
            ->get();

        $deletedImages = 0;
        $deletedProducts = 0;

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    $cloudinary->delete($image->public_id);

                    $deletedImages++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Borrado definitivo
            |--------------------------------------------------------------------------
            |
            | El borrado en cascada de la base de datos elimina las variantes,
            | proveedores e imágenes asociadas al producto.
            |
            */

            $product->forceDelete();

            $deletedProducts++;
        }

        $this->info("Productos purgados: {$deletedProducts}");
        $this->info("Imágenes eliminadas de Cloudinary: {$deletedImages}");

        return self::SUCCESS;
    }
}
