<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portal_notifications')) {
            return;
        }

        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 32)->default('system');
            $table->string('type', 64)->default('system');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('email_status', 16)->default('none');
            $table->unsignedInteger('email_attempts')->default(0);
            $table->timestamp('emailed_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
    }
};
