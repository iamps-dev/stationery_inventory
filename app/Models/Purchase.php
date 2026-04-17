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

    protected $casts = [
        'quantity' => 'decimal:2',
        'units_per_pack' => 'decimal:2',
        'quantity_in_pieces' => 'decimal:2',
        'purchase_price_per_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
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