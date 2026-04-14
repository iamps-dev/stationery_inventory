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
        // ✅ validation
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'unit_type' => 'required|in:piece,pack',
            'price_type' => 'required|in:per_piece,per_pack',
            'price' => 'required|numeric|min:0',
            'units_per_pack' => 'nullable|numeric|min:1',
        ]);

        // 👉 product check
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // 👉 units
        $unitsPerPack = $request->units_per_pack ?? $product->units_per_pack ?? 1;

        // 👉 quantity → pieces
        $quantityInPieces = ($request->unit_type === 'piece')
            ? $request->quantity
            : $request->quantity * $unitsPerPack;

        // 👉 selling price → per piece
        $sellingPricePerPiece = ($request->price_type === 'per_piece')
            ? $request->price
            : $request->price / $unitsPerPack;

        // 👉 get cost price from last purchase
        $lastPurchase = Purchase::where('product_id', $request->product_id)
            ->latest()
            ->first();

        $costPricePerPiece = $lastPurchase
            ? (float) $lastPurchase->purchase_price_per_unit
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
            return response()->json([
                'message' => 'Not enough stock',
                'available_stock' => $stock->total_quantity
            ], 400);
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

        // ✅ refresh stock for correct remaining quantity
        $stock->refresh();

        return response()->json([
            'message' => 'Sale saved successfully',
            'data' => [
                ...$sale->load('product')->toArray(),
                'remaining_stock' => $stock->total_quantity,
                'current_stock' => $stock->total_quantity,
            ]
        ]);
    }

    // ✅ LIST ALL SALES
    public function index()
    {
        $sales = Sale::with('product')->latest()->get();

        $sales->transform(function ($sale) {
            $stock = Stock::where('product_id', $sale->product_id)->first();

            $sale->current_stock = $stock ? $stock->total_quantity : 0;
            $sale->remaining_stock = $stock ? $stock->total_quantity : 0;

            return $sale;
        });

        return response()->json([
            'message' => 'Sales fetched successfully',
            'data' => $sales
        ]);
    }

    // ✅ SINGLE SALE
    public function show($id)
    {
        $sale = Sale::with('product')->find($id);

        if (!$sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        $stock = Stock::where('product_id', $sale->product_id)->first();

        $sale->current_stock = $stock ? $stock->total_quantity : 0;
        $sale->remaining_stock = $stock ? $stock->total_quantity : 0;

        return response()->json([
            'message' => 'Sale fetched successfully',
            'data' => $sale
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // ✅ optional: restore stock before delete
        $stock = Stock::where('product_id', $sale->product_id)->first();
        if ($stock) {
            $stock->increment('total_quantity', $sale->quantity_in_pieces);
        }

        $sale->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}