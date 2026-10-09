<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('compare_price');
        });

        // Los productos existentes ya guardan el precio rebajado en sale_price
        // y el precio anterior en compare_price. Conservamos ambos y recuperamos
        // el porcentaje para que el nuevo campo represente la promoción actual.
        DB::table('product_variants')
            ->whereNotNull('compare_price')
            ->where('compare_price', '>', 0)
            ->whereColumn('compare_price', '>', 'sale_price')
            ->update([
                'discount_percent' => DB::raw('ROUND((1 - (sale_price / compare_price)) * 100, 2)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
    }
};
