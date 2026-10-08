<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Deputy runs open-weight models only: agents get a temperature instead of Claude's effort, and the API provider becomes "hosted". */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->decimal('temperature', 3, 2)->default(0.3)->after('fallback_model_id');
        });
        Schema::table('ai_agents', fn (Blueprint $table) => $table->dropColumn('effort'));
        DB::table('ai_models')->where('provider', 'anthropic')->update(['provider' => 'hosted']);
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->string('effort')->default('medium');
        });
        Schema::table('ai_agents', fn (Blueprint $table) => $table->dropColumn('temperature'));
    }
};
