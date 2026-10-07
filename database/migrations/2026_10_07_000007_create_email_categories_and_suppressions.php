<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Rodeo → Emails: template categories and the suppression list (addresses no email goes to). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#00AEEF');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::table('email_templates', function (Blueprint $table) {
            $table->foreignId('email_category_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::create('email_suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('reason');                 // bounce | unsubscribe | complaint | manual
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_suppressions');
        Schema::table('email_templates', fn (Blueprint $t) => $t->dropConstrainedForeignId('email_category_id'));
        Schema::dropIfExists('email_categories');
    }
};
