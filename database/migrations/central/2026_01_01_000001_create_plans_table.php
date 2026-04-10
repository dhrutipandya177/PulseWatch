<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('price_cents');
            $table->string('currency', 10)->default('usd');
            $table->string('interval', 20)->default('monthly');
            $table->integer('trial_days')->default(14);
            $table->integer('max_monitors')->default(5);
            $table->integer('check_interval_seconds')->default(300);
            $table->boolean('has_status_page')->default(true);
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_team_members')->default(false);
            $table->integer('max_team_members')->default(1);
            $table->boolean('has_email_notifications')->default(true);
            $table->boolean('has_sms_notifications')->default(false);
            $table->boolean('has_slack_notifications')->default(false);
            $table->boolean('has_webhook_notifications')->default(false);
            $table->boolean('has_ssl_monitoring')->default(false);
            $table->integer('data_retention_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->string('stripe_price_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
