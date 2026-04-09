<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

    $table->integer('quantity');
    $table->string('unit_type');

    $table->integer('units_per_pack')->default(1);

    $table->string('price_type'); // per_piece / per_pack

    $table->integer('quantity_in_pieces');

    $table->decimal('purchase_price_per_unit', 10, 2);
    $table->decimal('total_price', 10, 2);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
