<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squarespace_address_entries', function (Blueprint $table) {
            $table->id();
            $table->string('squarespace_contact_id');
            $table->string('address_book_entry_id')->unique();
            $table->foreignId('shipping_address_id')->nullable()->constrained()->nullOnDelete();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('squarespace_contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squarespace_address_entries');
    }
};
