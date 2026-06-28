<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squarespace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('squarespace_order_id')->unique();
            $table->string('order_number')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->string('channel')->nullable();
            $table->string('currency', 8)->nullable();
            $table->integer('subtotal_cents')->nullable();
            $table->integer('shipping_total_cents')->nullable();
            $table->integer('tax_total_cents')->nullable();
            $table->integer('grand_total_cents')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('customer_email');
        });

        Schema::create('squarespace_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('squarespace_order_id')
                ->constrained('squarespace_orders')
                ->cascadeOnDelete();
            $table->string('line_item_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->json('variant_options')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->integer('unit_price_cents')->nullable();
            $table->integer('total_price_cents')->nullable();
            $table->string('image_url')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index('sku');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squarespace_order_items');
        Schema::dropIfExists('squarespace_orders');
    }
};
