<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceAccount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function accountnumber()
    {
        return $this->belongsTo(Accountnumber::class);
    }
}
