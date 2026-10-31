<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('recovery_token', 64)
                ->nullable()
                ->unique()
                ->after('session_id')
                ->comment('Token aleatorio para retomar el carrito.');

            $table->timestamp('last_activity_at')
                ->nullable()
                ->index()
                ->after('status')
                ->comment('Última modificación real del carrito.');

            $table->timestamp('recovery_email_sent_at')
                ->nullable()
                ->after('last_activity_at');

            $table->unsignedInteger('recovery_email_count')
                ->default(0)
                ->after('recovery_email_sent_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('marketing_opt_out_at')
                ->nullable()
                ->after('is_guest')
                ->comment('Baja de recordatorios comerciales de carrito.');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn([
                'recovery_token',
                'last_activity_at',
                'recovery_email_sent_at',
                'recovery_email_count',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketing_opt_out_at');
        });
    }
};
