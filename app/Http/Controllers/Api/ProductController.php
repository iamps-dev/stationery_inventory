<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
  public function index()
{
    return response()->json(
        Product::orderBy('created_at', 'desc')->get()
    );
}

    // ✅ STORE
    public function store(Request $request)
    {
        $product = Product::create([
            'name' => $request->name,
            'company_name' => $request->company_name, // ✅ ADDED
            'base_unit' => 'piece',
            'default_unit_type' => $request->default_unit_type,
            'units_per_pack' => $request->units_per_pack ?? 1,
        ]);

        return response()->json([
            'message' => 'Product created',
            'data' => $product
        ]);
    }

    // ✅ SHOW
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($product);
    }

    // ✅ UPDATE
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->update([
            'name' => $request->name,
            'company_name' => $request->company_name, // ✅ ADDED
            'default_unit_type' => $request->default_unit_type,
            'units_per_pack' => $request->units_per_pack ?? 1,
        ]);

        return response()->json([
            'message' => 'Product updated',
            'data' => $product
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}