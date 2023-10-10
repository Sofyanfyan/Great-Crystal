<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;


    protected $fillable = [
      'id',
      'name',
      'teacher_id',
      'created_at',
      'updated_at',
    ];

    public function teacher()
    {
      return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function student()
    {
      return $this->hasMany(Student::class);
    }

    public function payment_grade()
    {
      return $this->hasMany(Payment_grade::class);
    }

    public function spp()
    {
      return $this->hasOne(Payment_grade::class);
    }

    public function uniform()
    {
      return $this->hasOne(Payment_grade::class);
    }

    public function book()
    {
      return $this->hasOne(Payment_grade::class);
    }

    public function bundle()
    {
      return $this->hasOne(Payment_grade::class);
    }

    public function type()
    {
      return $this->hasOne(Payment_grade::class);
    }
}