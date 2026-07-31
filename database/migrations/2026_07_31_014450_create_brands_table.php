<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {

            $table->id();

            // Nombre de la marca
            $table->string('name', 150)
                ->comment('Nombre de la marca');

            // URL amigable
            $table->string('slug', 180)
                ->unique()
                ->comment('Slug único para URLs');

            // Descripción
            $table->text('description')
                ->nullable()
                ->comment('Descripción de la marca');

            // Logo
            $table->string('image')
                ->nullable()
                ->comment('Ruta o URL del logo');

            // Estado
            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado de la marca');

            // Orden de visualización
            $table->unsignedInteger('sort_order')
                ->default(0)
                ->comment('Orden de visualización');

            // Auditoría
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que creó el registro');

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Último usuario que modificó el registro');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('name');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
