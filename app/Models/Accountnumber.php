<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accountnumber extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function accountcategory()
    {
        return $this->belongsTo(Accountcategory::class, 'account_category_id');
    }

    public function calculateEndingBalance()
    {
        return $this->beginning_balance + $this->transactions_total;
    }

    public function getBalanceType()
    {
        return $this->ending_balance >= 0 ? 'debit' : 'kredit';
    }

    // public function beginningBalances()
    // {
    //     return $this->hasMany(BalanceAccount::class, 'accountnumber_id');
    // }
}
