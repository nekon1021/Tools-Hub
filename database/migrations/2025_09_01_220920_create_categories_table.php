<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
             $table->id();
             $table->string('name', 80);
             $table->string('slug', 120)->unique();
             $table->unsignedSmallInteger('sort_order')->default(0);
             $table->boolean('is_active')->default(true);
             $table->timestamps();
             $table->softDeletes();

             $table->unique(['name']);
             $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
