<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('invited')->after('role');
            $table->index('status');
        });

        DB::table('users')->where('must_reset_password', true)->update(['status' => 'invited']);
        DB::table('users')->whereIn('id', function ($query) {
            $query->select('user_id')
                ->from('student_profiles')
                ->whereNotNull('onboarding_completed_at');
        })->update(['status' => 'active']);
        DB::table('users')
            ->where('status', 'invited')
            ->where('must_reset_password', false)
            ->update(['status' => 'incomplete']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
