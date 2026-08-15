<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_zone_id')
                ->constrained('shipping_zones')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();

            $table->decimal('price', 10, 2);

            $table->boolean('status')
                ->default(true);

            $table->timestamps();

            $table->index([
                'supplier_id',
                'shipping_zone_id',
            ]);

            $table->index([
                'product_id',
                'product_variant_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
