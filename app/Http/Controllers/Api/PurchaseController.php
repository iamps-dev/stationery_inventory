<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;

class PurchaseController extends Controller
{
    // ✅ STORE PURCHASE
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric|min:1',
            'unit_type' => 'required|in:piece,pack',
            'units_per_pack' => 'nullable|numeric|min:1',
            'price_type' => 'required|in:per_piece,per_pack',
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::find($request->product_id);
        $supplier = Supplier::find($request->supplier_id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        if (!$supplier) {
            return response()->json([
                'message' => 'Supplier not found'
            ], 404);
        }

        $unitsPerPack = $request->units_per_pack ?? $product->units_per_pack ?? 1;

        if ($unitsPerPack <= 0) {
            return response()->json([
                'message' => 'Invalid units per pack'
            ], 400);
        }

        $quantityInPieces = ($request->unit_type === 'piece')
            ? $request->quantity
            : $request->quantity * $unitsPerPack;

        $price = $request->price;

        $pricePerPiece = ($request->price_type === 'per_piece')
            ? $price
            : ($price / $unitsPerPack);

        $totalPrice = $quantityInPieces * $pricePerPiece;

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

        $stock = Stock::firstOrCreate(
            ['product_id' => $request->product_id],
            ['total_quantity' => 0]
        );

        $stock->increment('total_quantity', $quantityInPieces);
        $stock->refresh();

        return response()->json([
            'message' => 'Purchase saved successfully',
            'data' => $purchase->load('product', 'supplier'),
            'current_stock' => $stock->total_quantity,
        ], 201);
    }

    // ✅ LIST
    public function index()
    {
        $purchases = Purchase::with('product', 'supplier')
            ->latest()
            ->get()
            ->map(function ($purchase) {
                $purchase->current_stock = Stock::where('product_id', $purchase->product_id)
                    ->value('total_quantity') ?? 0;
                return $purchase;
            });

        return response()->json($purchases);
    }

    // ✅ SHOW
    public function show($id)
    {
        $purchase = Purchase::with('product', 'supplier')->find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found'
            ], 404);
        }

        $currentStock = Stock::where('product_id', $purchase->product_id)
            ->value('total_quantity') ?? 0;

        return response()->json([
            'data' => $purchase,
            'current_stock' => $currentStock,
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found'
            ], 404);
        }

        $stock = Stock::where('product_id', $purchase->product_id)->first();

        if ($stock) {
            $newQty = max(0, $stock->total_quantity - $purchase->quantity_in_pieces);
            $stock->update([
                'total_quantity' => $newQty
            ]);
        }

        $purchase->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}