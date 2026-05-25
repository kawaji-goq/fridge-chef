<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_inventory_uses', function (Blueprint $table) {
            $table->foreignUuid('adoption_id')->constrained('adoptions')->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items');
            $table->decimal('used_quantity', 14, 4);
            $table->foreignId('used_unit_id')->constrained('units');
            $table->decimal('used_base_quantity', 14, 4);
            $table->timestamps();

            $table->primary(['adoption_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_inventory_uses');
    }
};
