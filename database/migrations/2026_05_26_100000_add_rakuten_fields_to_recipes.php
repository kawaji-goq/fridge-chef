<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // 楽天レシピなど構造化できない材料リストの保存用（["豚バラ肉 300g", ...]）
            $table->json('materials_text')->nullable()->after('instructions_beginner');
            // レシピ画像 URL（楽天など）
            $table->string('image_url', 512)->nullable()->after('materials_text');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['materials_text', 'image_url']);
        });
    }
};
