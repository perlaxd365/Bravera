<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)
                ->unique()
                ->comment('Código que ingresa el cliente.');

            $table->string('name', 150)
                ->comment('Nombre interno del cupón.');

            /*
            |--------------------------------------------------------------------------
            | Reglas de descuento
            |--------------------------------------------------------------------------
            */

            $table->string('type', 20)
                ->default('percentage')
                ->comment('percentage | fixed');

            $table->decimal('value', 10, 2)
                ->comment('Porcentaje (0-100) o monto fijo en S/.');

            $table->decimal('min_subtotal', 10, 2)
                ->default(0)
                ->comment('Monto mínimo de compra para usarlo.');

            $table->decimal('max_discount', 10, 2)
                ->nullable()
                ->comment('Tope máximo del descuento (porcentaje).');

            $table->dateTime('starts_at')
                ->nullable()
                ->comment('Inicio de vigencia.');

            $table->dateTime('ends_at')
                ->nullable()
                ->comment('Fin de vigencia.');

            /*
            |--------------------------------------------------------------------------
            | Límites de uso
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('usage_limit')
                ->nullable()
                ->comment('Uso máximo total (null = ilimitado).');

            $table->unsignedInteger('per_user_limit')
                ->default(1)
                ->comment('Uso máximo por cliente.');

            /*
            |--------------------------------------------------------------------------
            | Alcance del cupón
            |--------------------------------------------------------------------------
            */

            $table->string('applies_to', 20)
                ->default('all')
                ->comment('all | supplier | product | category');

            $table->foreignId('applies_to_id')
                ->nullable()
                ->comment('ID del supplier/product/category si aplica restricción.');

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedBigInteger('created_by')
                ->nullable();

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
