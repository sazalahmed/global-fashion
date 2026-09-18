<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('template');
            $table->boolean('is_active')->default(false);

            // Hero section
            $table->text('hero_title');
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image')->nullable();

            // Pricing display
            $table->decimal('offer_price', 15, 2)->nullable();
            $table->decimal('original_price', 15, 2)->nullable();

            // Content
            $table->string('video_url')->nullable();
            $table->json('sections')->nullable();
            $table->json('product_ids');

            // Delivery & contact
            $table->decimal('delivery_inside_dhaka', 10, 2)->default(60);
            $table->decimal('delivery_outside_dhaka', 10, 2)->default(120);
            $table->string('contact_phone')->nullable();

            // Customization
            $table->text('custom_css')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Audit
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
