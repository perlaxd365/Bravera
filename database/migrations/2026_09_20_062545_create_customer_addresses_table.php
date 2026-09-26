<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Cliente al que pertenece la dirección.');

            $table->string('full_name', 150)
                ->comment('Nombre completo del receptor.');

            $table->string('phone', 30)
                ->nullable()
                ->comment('Teléfono de contacto.');

            /*
            |--------------------------------------------------------------------------
            | Ubicación (UBIGEO)
            |--------------------------------------------------------------------------
            */

            $table->foreignId('location_id')
                ->constrained('locations')
                ->restrictOnDelete()
                ->comment('Distrito de destino (Location level = district).');

            $table->string('address')
                ->comment('Dirección, calle, avenida, número.');

            $table->string('reference')
                ->nullable()
                ->comment('Referencia del domicilio.');

            $table->boolean('is_default')
                ->default(false)
                ->comment('Dirección principal del cliente.');

            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
