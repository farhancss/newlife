<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->string('name', 80);
            $table->string('tagline')->nullable();
            $table->unsignedInteger('price_cents');
            $table->unsignedTinyInteger('container_count')->default(1);
            $table->boolean('includes_move_out_cycle')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('package_tier')->constrained('packages')->nullOnDelete();
        });

        $legacyMap = [
            'basic' => 'essential',
            'standard' => 'summit',
            'premium' => 'legacy',
        ];

        foreach ($legacyMap as $from => $to) {
            DB::table('student_profiles')->where('package_tier', $from)->update(['package_tier' => $to]);
        }
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_id');
        });

        Schema::dropIfExists('packages');
    }
};
