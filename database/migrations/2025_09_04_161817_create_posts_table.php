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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // 所有者
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // カテゴリー（任意）
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();
            $table->index('category_id');

            // 本体
            $table->string('title', 150);
            $table->string('slug', 200)->nullable(); // null 可（unique でも null は重複可）
            $table->longText('body');

            // 追加フィールド
            $table->text('lead')->nullable();      // 導入文
            $table->json('toc_json')->nullable();  // 目次(JSON)

            // 広告設定
            $table->boolean('show_ad_under_lead')->default(true);
            $table->boolean('show_ad_in_body')->default(true);
            $table->unsignedTinyInteger('ad_in_body_max')->default(2);
            $table->boolean('show_ad_below')->default(true);

            // SEO
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->string('og_image_path', 255)->nullable();

            // 公開制御
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // インデックス
            $table->unique('slug');                         // DBレベルでも一意に
            $table->index(['is_published', 'published_at']); // 一覧用
            $table->fullText(['title', 'body']);            // 検索用（MySQL 5.7+ / 8.x）
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
