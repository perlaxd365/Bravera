<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);

            $table->string('type', 30);

            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();

            $table->boolean('status')
                ->default(true);

            $table->timestamps();

            $table->index([
                'location_id',
                'type',
            ]);
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
