<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Stock extends Model
{
    protected $table = 'stock';

    protected $fillable = [
        'product_id',
        'total_quantity',
    ];

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}