<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAllergy extends Model
{
    protected $fillable = ['user_id', 'allergen_id', 'severity'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function allergen(): BelongsTo
    {
        return $this->belongsTo(Allergen::class);
    }
}
