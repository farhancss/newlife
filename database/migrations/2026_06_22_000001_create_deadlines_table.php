<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->cascadeOnDelete();

            // Polymorphic link to the source record (Container, RetailPackage,
            // StudentProfile, …). Nullable so a deadline can exist standalone.
            $table->nullableMorphs('deadlinable');

            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status')->default('upcoming');
            $table->timestamp('due_at');
            $table->timestamp('completed_at')->nullable();

            // One-time delivery guards.
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('completed_notified_at')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
