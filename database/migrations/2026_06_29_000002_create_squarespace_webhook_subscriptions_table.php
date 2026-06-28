<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squarespace_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_id')->unique();
            $table->string('endpoint_url');
            $table->json('topics');
            // The signing secret returned by Squarespace at creation, used to
            // verify inbound notification signatures. Stored encrypted.
            $table->text('secret')->nullable();
            $table->string('website_id')->nullable();
            $table->string('client_id')->nullable();
            $table->timestamp('remote_created_on')->nullable();
            $table->timestamp('remote_updated_on')->nullable();
            $table->string('last_test_status')->nullable();
            $table->timestamp('last_test_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squarespace_webhook_subscriptions');
    }
};
