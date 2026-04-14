<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Supplier;

class Purchase extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_id',
        'quantity',
        'unit_type',
        'units_per_pack',
        'price_type',
        'quantity_in_pieces',
        'purchase_price_per_unit',
        'total_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}