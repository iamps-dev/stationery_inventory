<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;

class PurchaseController extends Controller
{
    // ✅ STORE PURCHASE
    public function store(Request $request)
    {
        // 👉 check product
        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        // 👉 units
        $unitsPerPack = $request->units_per_pack ?? $product->units_per_pack ?? 1;

        // 👉 quantity → pieces
        $quantityInPieces = ($request->unit_type == 'piece')
            ? $request->quantity
            : $request->quantity * $unitsPerPack;

        // 👉 price → per piece
        $pricePerPiece = ($request->price_type == 'per_piece')
            ? $request->price
            : $request->price / $unitsPerPack;

        // 👉 total
        $totalPrice = $quantityInPieces * $pricePerPiece;

        // 👉 save purchase
        $purchase = Purchase::create([
            'product_id' => $request->product_id,
            'supplier_id' => $request->supplier_id,
            'quantity' => $request->quantity,
            'unit_type' => $request->unit_type,
            'units_per_pack' => $unitsPerPack,
            'price_type' => $request->price_type,
            'quantity_in_pieces' => $quantityInPieces,
            'purchase_price_per_unit' => $pricePerPiece,
            'total_price' => $totalPrice,
        ]);

        // 🔥 STOCK ADD
        $stock = Stock::firstOrCreate(
            ['product_id' => $request->product_id],
            ['total_quantity' => 0]
        );

        $stock->increment('total_quantity', $quantityInPieces);

        // 🔥 RETURN WITH RELATIONS
        return response()->json([
            'message' => 'Purchase saved successfully',
            'data' => $purchase->load('product', 'supplier'),
            'current_stock' => $stock->total_quantity
        ]);
    }

    // ✅ LIST
    public function index()
    {
        return Purchase::with('product', 'supplier')
            ->latest()
            ->get();
    }

    // ✅ SHOW
    public function show($id)
    {
        return Purchase::with('product', 'supplier')->find($id);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $purchase->delete();

        return response()->json(['message' => 'Deleted']);
    }
}