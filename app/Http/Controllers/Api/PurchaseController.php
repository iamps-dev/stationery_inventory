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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'     => 'required|integer|exists:products,id',
            'supplier_id'    => 'required|integer|exists:suppliers,id',
            'quantity'       => 'required|numeric|min:1',
            'unit_type'      => 'required|in:piece,pack,dozen,box,bundle,ream',
            'units_per_pack' => 'required|numeric|min:1',
            'price_type'     => 'required|in:per_piece,per_pack',
            'price'          => 'required|numeric|min:0',
        ]);

        $product = Product::find($validated['product_id']);
        $supplier = Supplier::find($validated['supplier_id']);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $quantity = (float) $validated['quantity'];
        $unitsPerPack = (float) $validated['units_per_pack'];
        $price = (float) $validated['price'];
        $unitType = $validated['unit_type'];
        $priceType = $validated['price_type'];

        $quantityInPieces = $quantity * $unitsPerPack;

        if ($priceType === 'per_piece') {
            $pricePerPiece = $price;
            $totalPrice = $quantityInPieces * $pricePerPiece;
        } else {
            $pricePerPiece = $price / $unitsPerPack;
            $totalPrice = $quantity * $price;
        }

        $purchase = Purchase::create([
            'product_id'              => $validated['product_id'],
            'supplier_id'             => $validated['supplier_id'],
            'quantity'                => $quantity,
            'unit_type'               => $unitType,
            'units_per_pack'          => $unitsPerPack,
            'price_type'              => $priceType,
            'quantity_in_pieces'      => round($quantityInPieces, 2),
            'purchase_price_per_unit' => round($pricePerPiece, 2),
            'total_price'             => round($totalPrice, 2),
        ]);

        $stock = Stock::firstOrCreate(
            ['product_id' => $validated['product_id']],
            ['total_quantity' => 0]
        );

        $stock->total_quantity = (float) $stock->total_quantity + $quantityInPieces;
        $stock->save();
        $stock->refresh();

        return response()->json([
            'message' => 'Purchase saved successfully',
            'data' => $purchase->load('product', 'supplier'),
            'current_stock' => $stock->total_quantity,
        ], 201);
    }

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

        return response()->json([
            'message' => 'Purchases fetched successfully',
            'data' => $purchases,
        ]);
    }

    public function show($id)
    {
        $purchase = Purchase::with('product', 'supplier')->find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found',
            ], 404);
        }

        $currentStock = Stock::where('product_id', $purchase->product_id)
            ->value('total_quantity') ?? 0;

        return response()->json([
            'message' => 'Purchase fetched successfully',
            'data' => $purchase,
            'current_stock' => $currentStock,
        ]);
    }

    public function destroy($id)
    {
        $purchase = Purchase::find($id);

        if (!$purchase) {
            return response()->json([
                'message' => 'Purchase not found',
            ], 404);
        }

        $stock = Stock::where('product_id', $purchase->product_id)->first();

        if ($stock) {
            $newQty = max(0, (float) $stock->total_quantity - (float) $purchase->quantity_in_pieces);

            $stock->update([
                'total_quantity' => round($newQty, 2),
            ]);
        }

        $purchase->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}