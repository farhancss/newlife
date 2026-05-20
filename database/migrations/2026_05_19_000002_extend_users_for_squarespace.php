<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_reset_password')->default(false)->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('must_reset_password');
            $table->string('squarespace_contact_id')->nullable()->unique()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['squarespace_contact_id']);
            $table->dropColumn([
                'must_reset_password',
                'password_changed_at',
                'squarespace_contact_id',
            ]);
        });
    }
};
