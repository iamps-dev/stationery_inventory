<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stock';

    protected $fillable = [
        'product_id',
        'total_quantity',
    ];

    public $timestamps = false;
}