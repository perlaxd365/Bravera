<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('wholesale_price', 10, 2)
                ->nullable()
                ->comment('Precio unitario al por mayor.');

            $table->unsignedInteger('wholesale_min_quantity')
                ->nullable()
                ->comment('Cantidad mínima para aplicar el precio al por mayor.');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'wholesale_price',
                'wholesale_min_quantity',
            ]);
        });
    }
};
