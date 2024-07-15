<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accountcategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function accountnumbers()
    {
        return $this->hasMany(Accountnumber::class, 'account_category_id');
    }
}
