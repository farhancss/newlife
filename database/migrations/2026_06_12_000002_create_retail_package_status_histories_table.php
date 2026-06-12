<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('retail_package_status_histories')) {
            return;
        }

        Schema::create('retail_package_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retail_package_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['retail_package_id', 'created_at'], 'rpsh_package_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_package_status_histories');
    }
};
