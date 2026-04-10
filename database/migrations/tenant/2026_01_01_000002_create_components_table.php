<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('operational');
            $table->string('group_name')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('show_uptime_percentage')->default(true);
            $table->foreignId('monitor_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
