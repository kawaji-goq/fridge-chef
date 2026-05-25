<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdoptionInventoryUse extends Model
{
    protected $fillable = [
        'adoption_id', 'inventory_item_id', 'used_quantity', 'used_unit_id', 'used_base_quantity',
    ];

    protected $casts = [
        'used_quantity' => 'decimal:4',
        'used_base_quantity' => 'decimal:4',
    ];

    public function adoption(): BelongsTo
    {
        return $this->belongsTo(Adoption::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'used_unit_id');
    }
}
