<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            // Distinguishes the package "move shipment" container from containers
            // provisioned by an add-on purchase (e.g. Additional Container).
            $table->string('source', 16)->default('move')->after('status');
        });

        Schema::table('student_add_ons', function (Blueprint $table) {
            $table->foreignId('container_id')->nullable()->after('status')
                ->constrained('containers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_add_ons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('container_id');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
