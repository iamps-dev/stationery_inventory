<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Stock;

class SaleController extends Controller
{
    // ✅ STORE SALE
    public function store(Request $request)
    {
        // 👉 product check
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // 👉 units
        $unitsPerPack = $request->units_per_pack ?? $product->units_per_pack ?? 1;

        // 👉 quantity → pieces
        $quantityInPieces = ($request->unit_type == 'piece')
            ? $request->quantity
            : $request->quantity * $unitsPerPack;

        // 👉 selling price → per piece
        $sellingPricePerPiece = ($request->price_type == 'per_piece')
            ? $request->price
            : $request->price / $unitsPerPack;

        // 👉 get cost price from last purchase
        $lastPurchase = Purchase::where('product_id', $request->product_id)
            ->latest()
            ->first();

        $costPricePerPiece = $lastPurchase
            ? $lastPurchase->purchase_price_per_unit
            : 0;

        // 👉 calculations
        $totalAmount = $quantityInPieces * $sellingPricePerPiece;
        $totalCost = $quantityInPieces * $costPricePerPiece;
        $profit = $totalAmount - $totalCost;

        // 🔥 stock check
        $stock = Stock::where('product_id', $request->product_id)->first();

        if (!$stock) {
            return response()->json(['message' => 'Stock not found'], 404);
        }

        if ($stock->total_quantity < $quantityInPieces) {
            return response()->json(['message' => 'Not enough stock'], 400);
        }

        // 👉 save sale
        $sale = Sale::create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'unit_type' => $request->unit_type,
            'units_per_pack' => $unitsPerPack,
            'price_type' => $request->price_type,
            'quantity_in_pieces' => $quantityInPieces,
            'selling_price_per_unit' => $sellingPricePerPiece,
            'cost_price_per_unit' => $costPricePerPiece,
            'total_amount' => $totalAmount,
            'total_cost' => $totalCost,
            'profit' => $profit,
        ]);

        // 🔥 stock minus
        $stock->decrement('total_quantity', $quantityInPieces);

        return response()->json([
            'message' => 'Sale saved successfully',
            'data' => $sale->load('product'),
            'remaining_stock' => $stock->total_quantity
        ]);
    }

    // ✅ LIST ALL SALES
    public function index()
    {
        return Sale::with('product')
            ->latest()
            ->get();
    }

    // ✅ SINGLE SALE
    public function show($id)
    {
        return Sale::with('product')->find($id);
    }

    // ✅ DELETE (optional)
    public function destroy($id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $sale->delete();

        return response()->json(['message' => 'Deleted']);
    }
}