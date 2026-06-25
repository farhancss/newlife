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
            $table->boolean('includes_storage')->default(false)->after('includes_move_out_cycle');
        });

        // Packages that bundle the end-of-year move-out cycle also include
        // summer storage, so seed the new flag from the existing one.
        DB::table('packages')
            ->where('includes_move_out_cycle', true)
            ->update(['includes_storage' => true]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('includes_storage');
        });
    }
};
