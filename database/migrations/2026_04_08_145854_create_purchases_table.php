<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

            $table->decimal('quantity', 12, 2);
            $table->enum('unit_type', ['piece', 'pack']);
            $table->integer('units_per_pack')->default(1);

            $table->enum('price_type', ['per_piece', 'per_pack']);
            $table->decimal('quantity_in_pieces', 12, 2)->default(0);
            $table->decimal('purchase_price_per_unit', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};