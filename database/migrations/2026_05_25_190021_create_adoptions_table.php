<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('proposal_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->foreignUuid('recipe_id')->constrained('recipes');
            $table->timestamp('adopted_at')->useCurrent();
            $table->decimal('servings', 6, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'adopted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoptions');
    }
};
