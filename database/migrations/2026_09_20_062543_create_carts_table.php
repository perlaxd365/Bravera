<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Cliente al que pertenece el carrito (null = invitado).');

            $table->string('session_id', 100)
                ->nullable()
                ->index()
                ->comment('Identificador de sesión para carritos invitados.');

            $table->string('status', 20)
                ->default('active')
                ->index()
                ->comment('active | abandoned | converted');

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
