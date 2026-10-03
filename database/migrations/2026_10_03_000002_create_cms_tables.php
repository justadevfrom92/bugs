<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Lando CMS: page templates, the site's page tree, and reusable content blocks. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();        // e.g. get-to-learnin/what-is-energy-choice, "/" for home
            $table->string('title');
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('redirect')->nullable();
            $table->string('status')->default('Draft');   // Published | Draft
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('site');
            $table->longText('html');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('templates');
    }
};
