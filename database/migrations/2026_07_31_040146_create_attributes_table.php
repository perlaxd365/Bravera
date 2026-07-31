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
        Schema::create('attributes', function (Blueprint $table) {

            $table->id();

            $table->string('name', 100)
                ->comment('Nombre del atributo');

            $table->string('slug', 120)
                ->unique()
                ->comment('Slug único');

            $table->enum('type', [
                'text',
                'number',
                'select',
                'color',
                'boolean',
            ])->default('select')
                ->comment('Tipo de atributo');

            $table->boolean('is_filter')
                ->default(true)
                ->comment('Se utiliza como filtro en la tienda');

            $table->boolean('is_required')
                ->default(false)
                ->comment('Obligatorio para crear variantes');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado del atributo');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Orden de visualización');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
