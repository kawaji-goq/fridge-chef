<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_unit_conversions', function (Blueprint $table) {
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('factor_to_base', 14, 6);
            $table->timestamps();

            $table->primary(['ingredient_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_unit_conversions');
    }
};
