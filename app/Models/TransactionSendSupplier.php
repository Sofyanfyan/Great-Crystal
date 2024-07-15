<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionSendSupplier extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function Transaction_send()
    {
        return $this->hasMany(Transaction_send::class, 'transaction_send_supplier_id');
    }
}
