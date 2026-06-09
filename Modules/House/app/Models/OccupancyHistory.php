<?php

namespace Modules\House\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Resident\Models\Resident;

// use Modules\House\Database\Factories\OccupancyHistoryFactory;

class OccupancyHistory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'house_id',
        'resident_id',
        'start_date',
        'end_date'
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }

    // protected static function newFactory(): OccupancyHistoryFactory
    // {
    //     // return OccupancyHistoryFactory::new();
    // }
}
