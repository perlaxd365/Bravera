<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('status');
        });

        DB::table('suppliers')->updateOrInsert(
            ['code' => 'BREVARE'],
            [
                'business_name' => 'Brevare S.A.C.',
                'trade_name' => 'Brevare',
                'status' => 'active',
                'is_internal' => true,
                'internal_notes' => 'Inventario propio de Brevare. No generar órdenes de compra a proveedor.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};
