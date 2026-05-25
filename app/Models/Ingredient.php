<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['slug', 'category', 'base_unit_id', 'external_refs'];

    protected $casts = [
        'external_refs' => 'array',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function localizations(): HasMany
    {
        return $this->hasMany(IngredientLocalization::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(IngredientAlias::class);
    }

    public function allergens(): BelongsToMany
    {
        return $this->belongsToMany(Allergen::class, 'ingredient_allergens');
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(IngredientUnitConversion::class);
    }

    public function nutrientValues(): HasMany
    {
        return $this->hasMany(IngredientNutrientValue::class);
    }

    public function displayName(string $locale = 'ja-JP'): string
    {
        return $this->localizations->firstWhere('locale', $locale)?->display_name
            ?? $this->slug;
    }
}
