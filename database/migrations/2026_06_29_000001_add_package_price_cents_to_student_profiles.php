<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('student_profiles', 'package_price_cents')) {
                // Grand total actually paid for the package (from the Squarespace
                // order), shown in the portal instead of the catalogue list price.
                $table->unsignedInteger('package_price_cents')->nullable()->after('package_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('student_profiles', 'package_price_cents')) {
                $table->dropColumn('package_price_cents');
            }
        });
    }
};
