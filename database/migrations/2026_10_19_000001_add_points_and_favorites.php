<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('points_balance')
                ->default(0)
                ->after('marketing_opt_out_at')
                ->comment('Saldo disponible de Brevare Puntos.');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('points_earned')
                ->default(0)
                ->after('meta_purchase_sent_at')
                ->comment('Puntos acreditados por esta compra.');

            $table->timestamp('points_awarded_at')
                ->nullable()
                ->after('points_earned')
                ->comment('Evita acreditar dos veces el mismo pedido.');
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->integer('points')
                ->comment('Positivo para ganar; negativo para canjear.');

            $table->unsignedInteger('balance_after');

            $table->string('type', 30);

            $table->string('description', 255);

            $table->string('reference_type', 50)->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'type', 'reference_type', 'reference_id'],
                'point_transactions_unique_reference'
            );
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('point_redemptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unsignedInteger('points');

            $table->decimal('amount', 10, 2);

            $table->string('status', 20)
                ->default('pending')
                ->index()
                ->comment('pending | approved | paid | rejected');

            $table->string('payout_method', 50)->nullable();

            $table->string('payout_reference', 100)->nullable();

            $table->text('admin_notes')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('point_redemptions');
        Schema::dropIfExists('point_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_awarded_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('points_balance');
        });
    }
};
