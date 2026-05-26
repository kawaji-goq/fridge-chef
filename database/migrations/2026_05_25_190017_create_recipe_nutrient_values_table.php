<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_nutrient_values', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('nutrient_id')->constrained('nutrients');
            $table->decimal('value_per_serving', 14, 4);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->primary(['recipe_id', 'nutrient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_nutrient_values');
    }
};
