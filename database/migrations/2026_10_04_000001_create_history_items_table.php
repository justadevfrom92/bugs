<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Every recorded event, named by model like the original system
 * (ItemPayment_model, ItemProductAutopay_model, ItemErcot81405_model, …).
 * Shown per customer in Corral → History and per admin user in Sheriff → Users → History.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();  // admin who did it (null = system/customer)
            $table->string('model', 80)->index();      // e.g. ItemPayment_model
            $table->string('group', 40)->index();      // e.g. Payments, Products, EDI Transactions
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('action', 20);              // created, updated, deleted, sent, received, viewed, logged
            $table->string('summary');
            $table->json('data')->nullable();          // snapshot of the record's fields
            $table->json('changes')->nullable();       // {field: [old, new]}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['customer_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['model', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_items');
    }
};
