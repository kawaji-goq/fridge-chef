<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_allergens', function (Blueprint $table) {
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained('allergens')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['ingredient_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_allergens');
    }
};
