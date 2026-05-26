<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nutrient extends Model
{
    protected $fillable = ['code', 'label_ja', 'unit'];
}
