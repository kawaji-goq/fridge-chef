<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalCandidate extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'proposal_id', 'recipe_id', 'recipe_snapshot', 'rank',
        'score', 'reason_text', 'missing_ingredients',
    ];

    protected $casts = [
        'recipe_snapshot' => 'array',
        'missing_ingredients' => 'array',
        'score' => 'decimal:4',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
