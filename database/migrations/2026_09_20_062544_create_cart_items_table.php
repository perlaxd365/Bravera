<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cart_id')
                ->constrained('carts')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('supplier_variant_id')
                ->nullable()
                ->constrained('supplier_variants')
                ->nullOnDelete()
                ->comment('Proveedor elegido al agregar al carrito.');

            $table->unsignedInteger('quantity')
                ->default(1)
                ->comment('Cantidad solicitada.');

            /*
            |--------------------------------------------------------------------------
            | Snapshot de precios
            |--------------------------------------------------------------------------
            */

            $table->decimal('unit_price', 10, 2)
                ->default(0)
                ->comment('Precio de venta al cliente en ese momento.');

            $table->decimal('unit_cost', 10, 2)
                ->default(0)
                ->comment('Costo de compra al proveedor en ese momento.');

            $table->decimal('supplier_shipping_cost', 10, 2)
                ->default(0)
                ->comment('Costo de envío del proveedor en ese momento.');

            $table->timestamps();

            $table->unique(
                ['cart_id', 'product_variant_id', 'supplier_variant_id'],
                'cart_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
