<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('investigating');
            $table->string('severity', 20)->default('minor');
            $table->foreignId('component_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('notify_subscribers')->default(true);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->text('message');
            $table->foreignId('user_id')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
    }
};
