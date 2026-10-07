<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The Send Test page keeps each test's subject, note and options so it can be sent again. */
    public function up(): void
    {
        Schema::table('email_test_sends', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('target');
            $table->text('note')->nullable()->after('subject');
            $table->json('params')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('email_test_sends', function (Blueprint $table) {
            $table->dropColumn(['subject', 'note', 'params']);
        });
    }
};
