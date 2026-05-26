<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_localizations', function (Blueprint $table) {
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('display_name', 128);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->primary(['ingredient_id', 'locale']);
            $table->index('display_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_localizations');
    }
};
