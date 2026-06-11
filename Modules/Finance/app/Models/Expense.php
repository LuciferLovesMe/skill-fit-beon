<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Finance\Database\Factories\ExpenseFactory;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'description',
        'amount',
        'expense_date'
    ];

    // protected static function newFactory(): ExpenseFactory
    // {
    //     // return ExpenseFactory::new();
    // }
}
