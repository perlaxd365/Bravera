<?php

namespace App\Console\Commands;

use Database\Seeders\ProductSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:reset {--seed : Seed the database after reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset catalog and sales tables for launch. Preserves categories, homepage settings (config images), users, roles and locations.';

    /**
     * Tablas a vaciar en orden (hijos primero para respetar FKs).
     *
     * @var array<int, string>
     */
    private const TABLES = [
        'cart_items',
        'carts',
        'supplier_order_items',
        'supplier_payments',
        'supplier_orders',
        'payments',
        'order_items',
        'claims',
        'orders',
        'coupon_usages',
        'coupons',
        'shipping_rates',
        'product_reviews',
        'supplier_variants',
        'product_images',
        'product_variant_attribute_values',
        'product_variants',
        'products',
        'attribute_values',
        'attributes',
        'brands',
        'suppliers',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->line('Eliminando tablas de ventas y catálogo (hard delete)...');

            DB::transaction(function () {
                foreach (self::TABLES as $table) {
                    $count = DB::table($table)->delete();
                    $this->line("{$table}: {$count} filas eliminadas");
                }
            });

            $this->info('✅ Catálogo y tablas de ventas reseteados correctamente.');

            if ($this->option('seed')) {
                $this->info('🌱 Ejecutando db:seed...');
                $this->call('db:seed');
                $this->info('📦 Ejecutando ProductSeeder...');
                $this->call('db:seed', ['--class' => ProductSeeder::class]);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Error al resetear catálogo: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
