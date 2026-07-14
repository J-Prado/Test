<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialist extends Model
{
    /** @use HasFactory<\Database\Factories\SpecialistFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'specialty',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }
}
