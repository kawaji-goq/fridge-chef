<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignUuid('recipe_id')->nullable()->constrained('recipes')->nullOnDelete();
            $table->json('recipe_snapshot')->nullable();
            $table->unsignedTinyInteger('rank');
            $table->decimal('score', 8, 4)->nullable();
            $table->text('reason_text')->nullable();
            $table->json('missing_ingredients')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_candidates');
    }
};
