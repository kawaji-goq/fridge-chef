<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_allergies', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('allergen_id')->constrained('allergens')->cascadeOnDelete();
            $table->enum('severity', ['avoid', 'strict'])->default('strict');
            $table->timestamps();

            $table->primary(['user_id', 'allergen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_allergies');
    }
};
