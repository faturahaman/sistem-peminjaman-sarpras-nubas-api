<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'photo'];

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function availableUnits(): HasMany
    {
        return $this->hasMany(Unit::class)->where('status', 'available');
    }
}
