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
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            // 旧スラッグ（/posts/{slug} の slug 部分）。DB検索用なのでユニークでOK
            $table->string('old_slug', 200)->unique();

            // 参照先の記事ID（削除時はリダイレクトも一緒に消える）
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
