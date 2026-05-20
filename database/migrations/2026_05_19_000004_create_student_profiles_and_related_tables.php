<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('squarespace_contact_id')->nullable()->unique();
            $table->string('new_life_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('school')->nullable();
            $table->string('incoming_year')->nullable();
            $table->string('package_tier')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->index('package_tier');
            $table->index('onboarding_completed_at');
        });

        Schema::create('parent_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('relationship')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('home');
            $table->string('line1')->nullable();
            $table->string('line2')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('phone')->nullable();
            $table->text('shipping_notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_profile_id', 'type']);
        });

        Schema::create('housing_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('university')->nullable();
            $table->string('residence_hall')->nullable();
            $table->string('building')->nullable();
            $table->string('room')->nullable();
            $table->date('move_in_date')->nullable();
            $table->string('move_in_window')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_infos');
        Schema::dropIfExists('shipping_addresses');
        Schema::dropIfExists('parent_guardians');
        Schema::dropIfExists('student_profiles');
    }
};
