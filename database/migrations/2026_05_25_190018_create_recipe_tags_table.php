<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_tags', function (Blueprint $table) {
            $table->foreignUuid('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->string('tag', 32);
            $table->timestamps();

            $table->primary(['recipe_id', 'tag']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_tags');
    }
};
