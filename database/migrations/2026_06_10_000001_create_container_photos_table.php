<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 32)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 64)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();

            $table->index(['container_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_photos');
    }
};
