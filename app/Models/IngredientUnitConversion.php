<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientUnitConversion extends Model
{
    protected $fillable = ['ingredient_id', 'unit_id', 'factor_to_base'];

    protected $casts = [
        'factor_to_base' => 'decimal:6',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
