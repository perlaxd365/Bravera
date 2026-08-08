<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->string('supplier_product_url', 1000)
                ->nullable()
                ->change();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->string('supplier_product_url', 255)
                ->nullable()
                ->change();
        });
    }
};
