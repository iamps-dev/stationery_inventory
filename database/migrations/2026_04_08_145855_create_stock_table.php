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
       Schema::create('stock', function (Blueprint $table) {
    $table->id();

    $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();

    $table->integer('total_quantity')->default(0); // always in pieces

    $table->timestamp('updated_at')->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
