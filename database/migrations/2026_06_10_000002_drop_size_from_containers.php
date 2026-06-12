<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('containers', 'size')) {
            Schema::table('containers', function (Blueprint $table) {
                $table->dropColumn('size');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('containers', 'size')) {
            Schema::table('containers', function (Blueprint $table) {
                $table->string('size', 10)->default('20ft')->after('code');
            });
        }
    }
};
