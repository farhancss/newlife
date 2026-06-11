<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('move_container_quantity')->default(1)->after('onboarding_completed_at');
            $table->timestamp('move_address_confirmed_at')->nullable()->after('move_container_quantity');
            $table->timestamp('move_shipment_triggered_at')->nullable()->after('move_address_confirmed_at');
        });

        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->string('size', 10)->default('20ft');
            $table->string('status', 40)->default('container_prepared');
            $table->string('location')->nullable();
            $table->string('outbound_tracking')->nullable();
            $table->string('return_tracking')->nullable();
            $table->date('ship_by_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id', 'status']);
            $table->index('status');
        });

        Schema::create('container_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['container_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_status_histories');
        Schema::dropIfExists('containers');

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'move_container_quantity',
                'move_address_confirmed_at',
                'move_shipment_triggered_at',
            ]);
        });
    }
};
