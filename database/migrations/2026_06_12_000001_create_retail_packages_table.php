<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('retail_packages')) {
            return;
        }

        Schema::create('retail_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('retailer', 64);
            $table->string('description');
            $table->string('tracking_number', 64);
            $table->date('estimated_arrival')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('logged');
            $table->string('removed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_profile_id', 'status']);
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_packages');
    }
};
