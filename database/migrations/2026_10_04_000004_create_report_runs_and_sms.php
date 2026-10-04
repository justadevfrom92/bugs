<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Recent Results" on the Corral report pages
        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('report');            // orders | notes | phonecalls
            $table->json('params');
            $table->unsignedInteger('rows')->default(0);
            $table->timestamp('created_at');
        });

        // SMS conversations: texts from customers come in, agents reply
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->string('direction')->default('out');   // out | in
            $table->string('phone')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
        Schema::table('contact_logs', function (Blueprint $table) {
            $table->dropColumn(['direction', 'phone']);
        });
    }
};
