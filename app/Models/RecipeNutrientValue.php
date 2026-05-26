<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeNutrientValue extends Model
{
    protected $fillable = ['recipe_id', 'nutrient_id', 'value_per_serving', 'calculated_at'];

    protected $casts = [
        'value_per_serving' => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function nutrient(): BelongsTo
    {
        return $this->belongsTo(Nutrient::class);
    }
}
