<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Relaciones
            |--------------------------------------------------------------------------
            */

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('supplier_variant_id')
                ->nullable()
                ->constrained('supplier_variants')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Snapshot del producto
            |--------------------------------------------------------------------------
            */

            $table->string('product_name')
                ->comment('Nombre del producto al momento de comprar.');

            $table->string('variant_sku', 100)
                ->nullable();

            $table->json('variant_attributes')
                ->nullable()
                ->comment('Atributos elegidos (color, talla, etc.).');

            $table->string('supplier_name')
                ->nullable()
                ->comment('Proveedor que surte el item.');

            $table->string('supplier_sku', 100)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Precios
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('unit_price', 10, 2)
                ->comment('Precio de venta al cliente.');

            $table->decimal('unit_cost', 10, 2)
                ->default(0)
                ->comment('Costo de compra al proveedor.');

            $table->decimal('supplier_shipping_cost', 10, 2)
                ->default(0)
                ->comment('Costo de envío del proveedor por unidad.');

            $table->decimal('shipping_price', 10, 2)
                ->default(0)
                ->comment('Tarifa de envío cobrada al cliente por este item.');

            $table->decimal('line_subtotal', 10, 2)
                ->comment('unit_price * quantity.');

            $table->decimal('line_cost_total', 10, 2)
                ->default(0)
                ->comment('(unit_cost + supplier_shipping_cost) * quantity.');

            $table->timestamps();

            $table->index(['order_id', 'supplier_id']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
