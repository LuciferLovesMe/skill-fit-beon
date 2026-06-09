<?php

namespace Modules\Resident\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\House\Models\OccupancyHistory;

// use Modules\Resident\Database\Factories\ResidentFactory;

class Resident extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
    ];

    public function occupancyHistories()
    {
        return $this->hasMany(OccupancyHistory::class);
    }
}
