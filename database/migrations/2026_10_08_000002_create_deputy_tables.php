<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Deputy — AI agents: the models (Claude and downloaded local ones), the agents that use them, and their conversations. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');                 // anthropic | local
            $table->string('model_id');                 // claude-opus-5-5, llama3.1:8b …
            $table->string('source')->nullable();       // where a downloaded model came from (file, URL, ollama pull name)
            $table->decimal('size_gb', 6, 2)->nullable();
            $table->string('quantization')->nullable(); // Q4_K_M …
            $table->unsignedInteger('context_window')->nullable();
            $table->string('status')->default('available');   // available | downloading | missing
            $table->timestamp('checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'model_id']);
        });
        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('activity');
            $table->string('description')->nullable();
            $table->foreignId('ai_model_id')->constrained()->restrictOnDelete();
            $table->foreignId('fallback_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('effort')->default('medium');
            $table->unsignedInteger('max_tokens')->default(4000);
            $table->text('system_prompt');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->string('topic');
            $table->string('outcome');
            $table->foreignId('handed_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // who ran a test
            $table->text('transcript');
            $table->unsignedInteger('duration_sec')->default(0);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamp('started_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('ai_agents');
        Schema::dropIfExists('ai_models');
    }
};
