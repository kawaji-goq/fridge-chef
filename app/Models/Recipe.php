<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'source_type', 'external_id', 'attribution_url', 'attribution_label',
        'title', 'locale', 'servings_default', 'total_cook_minutes',
        'instructions', 'instructions_beginner', 'created_by_user_id',
    ];

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function nutrientValues(): HasMany
    {
        return $this->hasMany(RecipeNutrientValue::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(RecipeTag::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
