<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('path')->unique();

            $table->text('description')->nullable();

            $table->string('image')->nullable();
            $table->string('icon')->nullable();

            $table->unsignedInteger('position')->default(0);

            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('parent_id');
            $table->index('slug');
            $table->index('path');
            $table->index('position');
            $table->index('is_visible');
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
