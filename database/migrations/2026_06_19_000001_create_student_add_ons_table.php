<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('add_on_slug', 64);
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->string('squarespace_url', 2048);
            $table->string('status', 32)->default('active');
            $table->string('squarespace_order_id')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('activated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_profile_id', 'status'], 'student_add_ons_profile_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_add_ons');
    }
};
