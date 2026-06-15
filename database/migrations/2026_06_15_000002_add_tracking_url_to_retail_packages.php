<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('retail_packages') || Schema::hasColumn('retail_packages', 'tracking_url')) {
            return;
        }

        Schema::table('retail_packages', function (Blueprint $table) {
            $table->string('tracking_url', 2048)->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('retail_packages') || ! Schema::hasColumn('retail_packages', 'tracking_url')) {
            return;
        }

        Schema::table('retail_packages', function (Blueprint $table) {
            $table->dropColumn('tracking_url');
        });
    }
};
