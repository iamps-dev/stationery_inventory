<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;

class ProductController extends Controller
{
    // ✅ GET PRODUCTS
    public function index()
    {
        $products = Product::with('stock')
            ->latest()
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'company_name' => $product->company_name,
                    'current_stock' => $product->stock
                        ? (int) $product->stock->total_quantity
                        : 0,
                ];
            });

        return response()->json($products);
    }

    // ✅ CREATE PRODUCT (ONLY NAME + COMPANY)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
        ]);

        // ✅ create product
        $product = Product::create([
            'name' => $request->name,
            'company_name' => $request->company_name,
        ]);

        // ✅ auto create stock
        Stock::create([
            'product_id' => $product->id,
            'total_quantity' => 0,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'company_name' => $product->company_name,
                'current_stock' => 0,
            ]
        ], 201);
    }

    // ✅ SHOW SINGLE
    public function show($id)
    {
        $product = Product::with('stock')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'company_name' => $product->company_name,
            'current_stock' => $product->stock
                ? (int) $product->stock->total_quantity
                : 0,
        ]);
    }

    // ✅ UPDATE
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->update([
            'name' => $request->name ?? $product->name,
            'company_name' => $request->company_name ?? $product->company_name,
        ]);

        return response()->json([
            'message' => 'Updated',
            'data' => $product
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}