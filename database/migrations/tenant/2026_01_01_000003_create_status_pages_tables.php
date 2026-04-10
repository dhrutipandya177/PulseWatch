<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('custom_domain')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('password_protected')->default(false);
            $table->string('password_hash')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('brand_color', 7)->default('#0ea5e9');
            $table->string('custom_css')->nullable();
            $table->string('footer_text')->nullable();
            $table->boolean('show_powered_by')->default(true);
            $table->boolean('show_subscribers')->default(true);
            $table->boolean('allow_subscriber_signup')->default(true);
            $table->integer('incident_retention_days')->default(90);
            $table->timestamps();
        });

        Schema::create('component_status_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['status_page_id', 'component_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_status_page');
        Schema::dropIfExists('status_pages');
    }
};
