<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('marketing_consent')
                ->default(false)
                ->after('notes')
                ->comment('Consentimiento para medición/publicidad.');

            $table->string('meta_purchase_event_id', 100)
                ->nullable()
                ->unique()
                ->after('marketing_consent')
                ->comment('ID compartido por Pixel y CAPI para deduplicar Purchase.');

            $table->timestamp('meta_purchase_sent_at')
                ->nullable()
                ->after('meta_purchase_event_id')
                ->comment('Último envío exitoso de Purchase por CAPI.');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'marketing_consent',
                'meta_purchase_event_id',
                'meta_purchase_sent_at',
            ]);
        });
    }
};
