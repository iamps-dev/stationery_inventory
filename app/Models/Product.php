<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Stock;

class Product extends Model
{
    protected $table = 'products';

    // ✅ ONLY 2 fields allowed
    protected $fillable = [
        'name',
        'company_name',
    ];

    public function stock()
    {
        return $this->hasOne(Stock::class, 'product_id', 'id');
    }
}