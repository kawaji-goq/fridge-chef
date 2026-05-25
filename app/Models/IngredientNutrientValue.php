<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientNutrientValue extends Model
{
    protected $fillable = ['ingredient_id', 'nutrient_id', 'value_per_100_base', 'source'];

    protected $casts = [
        'value_per_100_base' => 'decimal:4',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function nutrient(): BelongsTo
    {
        return $this->belongsTo(Nutrient::class);
    }
}
