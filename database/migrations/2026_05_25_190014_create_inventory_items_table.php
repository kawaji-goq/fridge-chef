<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained('ingredients');
            $table->decimal('quantity', 14, 4);
            $table->foreignId('unit_id')->constrained('units');
            $table->decimal('base_quantity', 14, 4);
            $table->enum('storage_location', ['fridge', 'freezer', 'pantry']);
            $table->date('expires_at')->nullable();
            $table->enum('expires_type', ['best_before', 'use_by'])->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'storage_location']);
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
