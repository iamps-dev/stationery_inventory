<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    // ✅ STORE SALE
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
            'unit_type' => 'required|in:piece,pack',
            'price_type' => 'required|in:per_piece,per_pack',
            'price' => 'required|numeric|min:0.01',
            'units_per_pack' => 'nullable|numeric|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $product = Product::find($request->product_id);

            if (!$product) {
                return response()->json([
                    'message' => 'Product not found'
                ], 404);
            }

            $unitsPerPack = (int) ($request->units_per_pack ?? $product->units_per_pack ?? 1);

            if ($unitsPerPack <= 0) {
                return response()->json([
                    'message' => 'Units per pack must be greater than 0'
                ], 422);
            }

            // ✅ quantity convert into pieces
            $quantityInPieces = $request->unit_type === 'piece'
                ? (int) $request->quantity
                : (int) $request->quantity * $unitsPerPack;

            // ✅ selling price convert into per piece
            $sellingPricePerPiece = $request->price_type === 'per_piece'
                ? (float) $request->price
                : ((float) $request->price / $unitsPerPack);

            // ✅ get latest purchase cost
            $lastPurchase = Purchase::where('product_id', $request->product_id)
                ->latest('id')
                ->first();

            $costPricePerPiece = $lastPurchase
                ? (float) $lastPurchase->purchase_price_per_unit
                : 0;

            $totalAmount = round($quantityInPieces * $sellingPricePerPiece, 2);
            $totalCost = round($quantityInPieces * $costPricePerPiece, 2);
            $profit = round($totalAmount - $totalCost, 2);

            // ✅ lock stock row during transaction
            $stock = Stock::where('product_id', $request->product_id)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                return response()->json([
                    'message' => 'Stock not found'
                ], 404);
            }

            if ((int) $stock->total_quantity < $quantityInPieces) {
                return response()->json([
                    'message' => 'Not enough stock',
                    'available_stock' => (int) $stock->total_quantity
                ], 400);
            }

            $sale = Sale::create([
                'product_id' => $request->product_id,
                'quantity' => (int) $request->quantity,
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

            $stock->decrement('total_quantity', $quantityInPieces);
            $stock->refresh();

            return response()->json([
                'message' => 'Sale saved successfully',
                'data' => [
                    ...$sale->load('product')->toArray(),
                    'remaining_stock' => (int) $stock->total_quantity,
                    'current_stock' => (int) $stock->total_quantity,
                ]
            ], 201);
        });
    }

    // ✅ GET ALL SALES
    public function index()
    {
        $sales = Sale::with('product')->latest('id')->get();

        $sales->transform(function ($sale) {
            $stock = Stock::where('product_id', $sale->product_id)->first();

            $sale->current_stock = $stock ? (int) $stock->total_quantity : 0;
            $sale->remaining_stock = $stock ? (int) $stock->total_quantity : 0;

            return $sale;
        });

        return response()->json([
            'message' => 'Sales fetched successfully',
            'data' => $sales
        ]);
    }

    // ✅ GET SINGLE SALE
    public function show($id)
    {
        $sale = Sale::with('product')->find($id);

        if (!$sale) {
            return response()->json([
                'message' => 'Sale not found'
            ], 404);
        }

        $stock = Stock::where('product_id', $sale->product_id)->first();

        $sale->current_stock = $stock ? (int) $stock->total_quantity : 0;
        $sale->remaining_stock = $stock ? (int) $stock->total_quantity : 0;

        return response()->json([
            'message' => 'Sale fetched successfully',
            'data' => $sale
        ]);
    }

    // ✅ DELETE SALE
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $sale = Sale::find($id);

            if (!$sale) {
                return response()->json([
                    'message' => 'Sale not found'
                ], 404);
            }

            $stock = Stock::where('product_id', $sale->product_id)
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->increment('total_quantity', $sale->quantity_in_pieces);
                $stock->refresh();
            }

            $sale->delete();

            return response()->json([
                'message' => 'Sale deleted successfully',
                'restored_stock' => $stock ? (int) $stock->total_quantity : null
            ]);
        });
    }
}