<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squarespace_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id')->unique();
            $table->string('topic');
            $table->string('website_id')->nullable();
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['topic', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squarespace_webhook_events');
    }
};
