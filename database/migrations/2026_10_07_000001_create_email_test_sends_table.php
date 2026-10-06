<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Rodeo → Email Templates → Send a Test: who a test went to and whether it was delivered. */
    public function up(): void
    {
        Schema::create('email_test_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mode');                 // emails | accounts | bookmarks | team | category
            $table->string('target')->nullable();   // what was picked, e.g. "Marketing" or "Good - On Flow · Residential"
            $table->json('recipients');             // [{email, account?, status, error?}]
            $table->string('status');               // sent | logged | failed | partial
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_test_sends');
    }
};
