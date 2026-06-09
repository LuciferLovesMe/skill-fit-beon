<?php

namespace Modules\House\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\House\Database\Factories\HouseFactory;

class House extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'block_number',
        'is_occupied'
    ];

    public function occupancyHistories()
    {
        return $this->hasMany(OccupancyHistory::class);
    }

    public function currentOccupant()
    {
        return $this->hasOne(OccupancyHistory::class)->whereNull('end_date');
    }

    // protected static function newFactory(): HouseFactory
    // {
    //     // return HouseFactory::new();
    // }
}
