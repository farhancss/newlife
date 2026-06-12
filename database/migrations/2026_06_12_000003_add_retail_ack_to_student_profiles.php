<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('student_profiles', 'retail_packages_acknowledged_at')) {
                $table->timestamp('retail_packages_acknowledged_at')->nullable()->after('move_shipment_triggered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'retail_packages_acknowledged_at')) {
                $table->dropColumn('retail_packages_acknowledged_at');
            }
        });
    }
};
