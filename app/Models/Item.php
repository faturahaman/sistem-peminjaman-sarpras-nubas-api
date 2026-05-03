<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;
    protected $fillable = ["name", "photo"];

    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value
                ? Storage::disk("public")->url($value)
                : null,
        );
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function availableUnits(): HasMany
    {
        return $this->hasMany(Unit::class)->where("status", "available");
    }
}
