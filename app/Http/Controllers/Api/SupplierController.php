<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    // ✅ GET ALL (WITH CACHE)
    public function index()
    {
        $suppliers = Cache::remember('suppliers_list', 600, function () {
            return Supplier::orderBy('created_at', 'desc')->get();
        });

        return response()->json($suppliers);
    }

    // ✅ STORE
    public function store(Request $request)
    {
        $supplier = Supplier::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'created_at' => now(),
        ]);

        // 🔥 CLEAR CACHE
        Cache::forget('suppliers_list');

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ]);
    }

    // ✅ SHOW SINGLE (WITH CACHE)
    public function show($id)
    {
        $supplier = Cache::remember("supplier_$id", 600, function () use ($id) {
            return Supplier::find($id);
        });

        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($supplier);
    }

    // ✅ UPDATE
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplier->update($request->only(['name', 'mobile', 'address']));

        // 🔥 CLEAR CACHE
        Cache::forget('suppliers_list');
        Cache::forget("supplier_$id");

        return response()->json([
            'message' => 'Supplier updated',
            'data' => $supplier
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $supplier->delete();

        // 🔥 CLEAR CACHE
        Cache::forget('suppliers_list');
        Cache::forget("supplier_$id");

        return response()->json(['message' => 'Supplier deleted']);
    }
}