<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('method', 10)->default('GET');
            $table->string('type', 20)->default('http');
            $table->integer('check_interval_seconds')->default(300);
            $table->integer('timeout_seconds')->default(30);
            $table->integer('expected_status_code')->default(200);
            $table->json('headers')->nullable();
            $table->json('body')->nullable();
            $table->json('expected_content')->nullable();
            $table->boolean('follow_redirects')->default(true);
            $table->boolean('verify_ssl')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('last_status')->nullable();
            $table->integer('last_response_time_ms')->nullable();
            $table->integer('last_status_code')->nullable();
            $table->string('last_error_message')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->boolean('status');
            $table->integer('response_time_ms')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('error_message')->nullable();
            $table->boolean('is_incident')->default(false);
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['monitor_id', 'checked_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_logs');
        Schema::dropIfExists('monitors');
    }
};
