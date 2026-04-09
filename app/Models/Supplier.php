<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'suppliers';

    public $timestamps = false; // kyunki tumne updated_at nahi banaya

    protected $fillable = [
        'name',
        'mobile',
        'address',
        'created_at',
    ];
}