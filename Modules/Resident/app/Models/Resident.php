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
        'id_card_photo',
        'phone_number',
        'is_married',
        'is_permanent',
    ];

    protected $appends = ['id_card_photo_url'];

    function getIdCardPhotoURL() : ?string
    {
        if (!$this->id_card_photo) {
            return asset('images/default-id-card-photo.png');
        }
        
        return null;
    }

    public function occupancyHistories()
    {
        return $this->hasMany(OccupancyHistory::class);
    }
}
