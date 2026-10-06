<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Walker (reporting): every report run with its status, and reports people upload. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_runs', function (Blueprint $table) {
            $table->string('app')->default('corral');          // where it was run from: corral | walker
            $table->string('title')->nullable();
            $table->string('model')->nullable();               // the model the report reads, e.g. ItemPayment_model
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->json('preview')->nullable();               // header + first rows, shown on the run's page
            $table->foreignId('rerun_of')->nullable()->constrained('report_runs')->nullOnDelete();
        });

        Schema::create('report_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('size');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_uploads');
        Schema::table('report_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rerun_of');
            $table->dropColumn(['app', 'title', 'model', 'started_at', 'finished_at', 'duration_ms', 'error', 'preview']);
        });
    }
};
