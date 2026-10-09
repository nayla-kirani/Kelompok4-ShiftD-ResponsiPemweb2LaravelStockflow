<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->default(0)->change();
            $table->decimal('min_stock_level', 12, 3)->default(10)->change();
            $table->decimal('max_stock_level', 12, 3)->default(1000)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
            $table->decimal('received_quantity', 12, 3)->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('min_stock_level')->default(10)->change();
            $table->integer('max_stock_level')->default(1000)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->integer('quantity')->change();
            $table->integer('received_quantity')->default(0)->change();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};
