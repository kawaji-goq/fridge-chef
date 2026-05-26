<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['user_id', 'household_adults', 'household_children'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
