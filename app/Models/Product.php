<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
       'name',
        'company_name', // ✅ ADDED
        'base_unit',
        'default_unit_type',
        'units_per_pack',
    ];
}