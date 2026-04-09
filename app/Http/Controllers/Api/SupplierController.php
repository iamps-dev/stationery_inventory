<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    // ✅ GET ALL
    public function index()
    {
        return response()->json(Supplier::all());
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

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier
        ]);
    }

    // ✅ SHOW SINGLE
    public function show($id)
    {
        $supplier = Supplier::find($id);

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

        return response()->json(['message' => 'Supplier deleted']);
    }
}