<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function invoices()
    {
        return $this->hasMany(InvoiceSupplier::class, 'supplier_id', 'id');
    }
}
