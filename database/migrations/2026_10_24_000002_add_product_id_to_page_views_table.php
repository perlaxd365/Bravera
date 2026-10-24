<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table): void {
            $table->foreignId('product_id')
                ->nullable()
                ->after('visitor_token')
                ->constrained()
                ->nullOnDelete();

            $table->index(
                ['product_id', 'viewed_at'],
                'page_views_product_date_index',
            );
        });

        DB::table('products')
            ->whereNull('deleted_at')
            ->select(['id', 'slug'])
            ->orderBy('id')
            ->chunkById(500, function ($products): void {
                foreach ($products as $product) {
                    DB::table('page_views')
                        ->where('path', '/producto/'.$product->slug)
                        ->update(['product_id' => $product->id]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table): void {
            $table->dropIndex('page_views_product_date_index');
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
