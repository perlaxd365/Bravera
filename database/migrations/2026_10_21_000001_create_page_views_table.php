<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table): void {
            $table->id();
            $table->string('visitor_token', 36);
            $table->string('path', 255);
            $table->timestamp('viewed_at')->index();

            $table->index(
                ['viewed_at', 'visitor_token'],
                'page_views_date_visitor_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
