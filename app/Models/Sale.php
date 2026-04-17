<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Sale extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'unit_type',
        'units_per_pack',
        'price_type',
        'quantity_in_pieces',
        'selling_price_per_unit',
        'cost_price_per_unit',
        'total_amount',
        'total_cost',
        'profit',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'units_per_pack' => 'integer',
        'quantity_in_pieces' => 'integer',
        'selling_price_per_unit' => 'float',
        'cost_price_per_unit' => 'float',
        'total_amount' => 'float',
        'total_cost' => 'float',
        'profit' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}