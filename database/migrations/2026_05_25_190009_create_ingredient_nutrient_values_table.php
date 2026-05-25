<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_nutrient_values', function (Blueprint $table) {
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('nutrient_id')->constrained('nutrients');
            $table->decimal('value_per_100_base', 14, 4);
            $table->string('source', 32);
            $table->timestamps();

            $table->primary(['ingredient_id', 'nutrient_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_nutrient_values');
    }
};
