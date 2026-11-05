<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->dropUnique('pvav_variant_attribute_unique');
            $table->unique(
                ['product_variant_id', 'attribute_value_id'],
                'pvav_variant_value_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->dropUnique('pvav_variant_value_unique');
            $table->unique(
                ['product_variant_id', 'attribute_id'],
                'pvav_variant_attribute_unique'
            );
        });
    }
};
