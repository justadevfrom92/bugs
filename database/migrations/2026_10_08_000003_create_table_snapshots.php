<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sheriff → Cached Tables: a copy of each admin page's table when it is visited, and of a table when a record is added to it. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('kind');                       // page | record
            $table->string('key')->index();               // page path, or the database table
            $table->string('title');
            $table->string('app')->nullable();            // corral, lando … for pages
            $table->longText('html')->nullable();         // page: the table as it showed (read-only copy)
            $table->json('columns')->nullable();          // record: column names
            $table->json('rows')->nullable();             // record: newest rows, new one included
            $table->unsignedBigInteger('record_id')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->string('hash', 40);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->index();
            $table->index(['kind', 'key', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_snapshots');
    }
};
