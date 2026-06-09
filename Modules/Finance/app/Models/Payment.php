<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\House\Models\House;
use Modules\Resident\Models\Resident;

// use Modules\Finance\Database\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'house_id',
        'resident_id',
        'fee_type',
        'month',
        'year',
        'billed_amount',
        'paid_amount',
        'payment_date',
        'is_paid'
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function resident()
    {
        return $this->belongsTo(Resident::class);
    }


    // protected static function newFactory(): PaymentFactory
    // {
    //     // return PaymentFactory::new();
    // }
}
