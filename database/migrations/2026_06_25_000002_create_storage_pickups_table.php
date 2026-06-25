<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('container_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 40)->default('requested');

            // Student-supplied request details.
            $table->date('requested_pickup_date');
            $table->string('pickup_location');
            $table->string('contact_phone', 40)->nullable();
            $table->unsignedSmallInteger('container_count')->nullable();
            $table->text('notes')->nullable();

            // Admin-side scheduling / fulfilment details.
            $table->date('confirmed_pickup_date')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_pickups');
    }
};
