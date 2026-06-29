<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Whether the package unlocks the retail-package tracking feature.
            $table->boolean('allows_retail_packages')->default(false)->after('includes_storage');
            // How many retail packages a student on this package may log.
            $table->unsignedTinyInteger('max_retail_packages')->default(0)->after('allows_retail_packages');
        });

        // Only the Legacy package bundles retail-package receiving (max 5).
        // Essentials and Summit do not include it out of the box; those
        // students unlock it by purchasing an add-on instead.
        DB::table('packages')->where('slug', 'legacy')->update([
            'allows_retail_packages' => true,
            'max_retail_packages' => 5,
        ]);

        DB::table('packages')->whereIn('slug', ['essential', 'summit'])->update([
            'allows_retail_packages' => false,
            'max_retail_packages' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['allows_retail_packages', 'max_retail_packages']);
        });
    }
};
