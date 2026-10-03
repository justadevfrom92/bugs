<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Sheriff: reference data rows and a log of scheduled job runs. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_rows', function (Blueprint $table) {
            $table->id();
            $table->string('table_key')->index();    // key from config('admin.reference_tables')
            $table->unsignedSmallInteger('position')->default(0);
            $table->json('cells');
            $table->timestamps();
        });

        Schema::create('job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command')->index();
            $table->string('status');               // Running | OK | Warning | Failed | Skipped
            $table->text('message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_runs');
        Schema::dropIfExists('reference_rows');
    }
};
