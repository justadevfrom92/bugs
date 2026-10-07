<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields of the original Corral item models (config/items.php) that have no
     * table of their own in this app: whole items (devices, reviews, usage
     * buckets…) and the extra original fields of items that do (record_id).
     */
    public function up(): void
    {
        Schema::create('data_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('model');
            $table->unsignedBigInteger('record_id')->nullable();   // the app record these extra fields belong to
            $table->string('summary')->nullable();                // shown next to the item on its parent ticket
            $table->json('data');
            $table->timestamps();
            $table->index(['model', 'record_id']);
            $table->index(['customer_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_items');
    }
};
