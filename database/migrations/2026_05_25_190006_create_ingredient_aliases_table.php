<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_aliases', function (Blueprint $table) {
            $table->foreignUuid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('alias', 128);
            $table->timestamps();

            $table->primary(['ingredient_id', 'locale', 'alias']);
            $table->index(['locale', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_aliases');
    }
};
