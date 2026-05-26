<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('ingredients');
            $table->decimal('quantity', 14, 4);
            $table->foreignId('unit_id')->constrained('units');
            $table->boolean('is_optional')->default(false);
            $table->string('display_text', 128)->nullable();
            $table->timestamps();

            $table->primary(['recipe_id', 'ingredient_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
