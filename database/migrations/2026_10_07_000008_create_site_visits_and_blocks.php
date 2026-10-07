<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lando → Site: page views on the website, and the IP addresses and areas it blocks. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 40)->index();          // random id in a cookie, one per browser
            $table->string('ip', 45)->index();
            $table->string('path')->index();                  // normalized: "/" or "plans", "myaccount/bills"
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();   // signed in to My Account
            $table->string('referrer')->nullable();
            $table->string('agent')->nullable();
            $table->boolean('blocked')->default(false);
            $table->timestamp('created_at')->index();
            $table->timestamp('seen_at')->index();            // last heartbeat from the open page
        });
        Schema::create('site_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type');                           // ip | area
            $table->string('value');                          // 203.0.113.7, 203.0.113.* or a path like wp-admin
            $table->string('reason')->nullable();
            $table->string('message')->nullable();            // shown to the visitor
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['type', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_blocks');
        Schema::dropIfExists('site_visits');
    }
};
