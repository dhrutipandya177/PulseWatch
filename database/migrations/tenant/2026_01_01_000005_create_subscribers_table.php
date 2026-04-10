<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('status_page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('component_subscriptions')->nullable();
            $table->timestamps();

            $table->unique(['email', 'status_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
