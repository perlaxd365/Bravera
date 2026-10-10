<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las URLs de Cloudinary (con el nombre original del archivo incluido)
     * pueden superar los 255 caracteres de un `string` estándar, lo que
     * provocaba el error de validación `max.string` al guardar categorías
     * y marcas. Se amplían a `text` para almacenarlas completas.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('image')->nullable()->change();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->text('image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
        });
    }
};
