<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('company_name')->nullable(); // ✅ ADDED

            $table->string('base_unit')->default('piece');

            $table->string('default_unit_type')->nullable(); // piece/dozen/box
            $table->integer('units_per_pack')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};