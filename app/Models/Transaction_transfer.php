<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction_transfer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function transferAccount()
    {
        return $this->belongsTo(AccountNumber::class, 'transfer_account_id');
    }

    public function depositAccount()
    {
        return $this->belongsTo(AccountNumber::class, 'deposit_account_id');
    }
}
