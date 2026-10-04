<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Admin apps created from the launcher's "New Admin App" button. The built-in
 * apps (Corral, Lando, Astro, Sheriff) stay in config/admin.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_apps', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();      // URL and permission key: /admin/<key>
            $table->string('name', 60);
            $table->string('description', 200);
            $table->string('icon', 20)->default('folder');
            $table->json('menu');                     // [{heading, label, route|url}]
            $table->unsignedSmallInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_apps');
    }
};
