<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sheriff → APIs → Configure: each "Test Connection" result. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_tests', function (Blueprint $table) {
            $table->id();
            $table->string('integration');
            $table->boolean('ok');
            $table->string('message', 500);
            $table->unsignedInteger('response_ms')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at');
            $table->index(['integration', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_tests');
    }
};
