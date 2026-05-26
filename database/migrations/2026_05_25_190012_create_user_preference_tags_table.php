<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preference_tags', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tag', 32);
            $table->tinyInteger('weight')->default(1);
            $table->timestamps();

            $table->primary(['user_id', 'tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preference_tags');
    }
};
