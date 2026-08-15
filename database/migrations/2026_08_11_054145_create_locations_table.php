<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identificación geográfica
            |--------------------------------------------------------------------------
            |
            | UBIGEO identifica de forma única el departamento, provincia
            | o distrito dentro de la división político-administrativa.
            |
            */
            $table->string('ubigeo', 6)
                ->unique()
                ->comment('Código UBIGEO de la ubicación');

            /*
            |--------------------------------------------------------------------------
            | Nombre
            |--------------------------------------------------------------------------
            */
            $table->string('name', 150)
                ->comment('Nombre de la ubicación');

            /*
            |--------------------------------------------------------------------------
            | Nivel geográfico
            |--------------------------------------------------------------------------
            |
            | department = Departamento
            | province   = Provincia
            | district   = Distrito
            |
            */
            $table->string('level', 20)
                ->comment('Nivel geográfico: department, province o district');

            /*
            |--------------------------------------------------------------------------
            | Jerarquía
            |--------------------------------------------------------------------------
            |
            | Departamento
            |     └── Provincia
            |             └── Distrito
            |
            */
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */
            $table->index('level');
            $table->index(['parent_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
