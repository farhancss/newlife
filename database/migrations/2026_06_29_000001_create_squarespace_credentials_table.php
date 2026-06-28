<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squarespace_credentials', function (Blueprint $table) {
            $table->id();
            // OAuth tokens are stored encrypted at the application layer.
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->nullable();
            $table->string('scopes')->nullable();
            $table->string('website_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squarespace_credentials');
    }
};
