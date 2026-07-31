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
        Schema::create('attribute_values', function (Blueprint $table) {

            $table->id();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Atributo al que pertenece');

            $table->string('value', 150)
                ->comment('Valor del atributo');

            $table->string('slug', 170)
                ->comment('Slug del valor');

            $table->string('color', 10)
                ->nullable()
                ->comment('Color hexadecimal cuando el atributo es tipo color');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Orden');

            $table->boolean('is_active')
                ->default(true)
                ->comment('Estado');

            $table->timestamps();

            $table->softDeletes();

            $table->unique(['attribute_id', 'slug']);

            $table->index(['attribute_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
