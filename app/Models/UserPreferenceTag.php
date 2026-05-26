<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreferenceTag extends Model
{
    protected $fillable = ['user_id', 'tag', 'weight'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
