<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('source_type', ['rakuten', 'ai_generated', 'user_created']);
            $table->string('external_id', 64)->nullable();
            $table->string('attribution_url', 512)->nullable();
            $table->string('attribution_label', 64)->nullable();
            $table->string('title', 255);
            $table->string('locale', 16)->default('ja-JP');
            $table->unsignedTinyInteger('servings_default')->default(2);
            $table->unsignedSmallInteger('total_cook_minutes')->nullable();
            $table->longText('instructions');
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
