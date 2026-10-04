<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The last fields from the original Lando "Edit Page" screen, and saved report files. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->longText('content_amp')->nullable();          // AMP Content: served at /amp/<path>
            $table->string('pricegrid_type')->nullable();          // config('cms.price_grids'); null = no price grid
            $table->string('pricegrid_header')->nullable();
            $table->foreignId('pricegrid_group_id')->nullable()->constrained('plan_groups')->nullOnDelete();
            $table->string('rating_formula')->nullable();          // config('cms.rating_formulas')
            $table->foreignId('copy_from_id')->nullable()->constrained('pages')->nullOnDelete();  // "Keep contents in sync"
            $table->boolean('hard_cache')->default(false);
            $table->unsignedSmallInteger('rep_id')->nullable();    // config('cms.reps')
            $table->string('market_label')->nullable();
            $table->string('address_box_title')->nullable();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('address_box_title')->nullable();       // the site default for the zip / address box
        });

        // Output - Backend: the report runs after the page returns and is saved for download
        Schema::table('report_runs', function (Blueprint $table) {
            $table->string('status')->default('done');             // running | done | failed
            $table->string('file')->nullable();                    // storage/app/private/reports/...
        });
    }

    public function down(): void
    {
        Schema::table('report_runs', fn (Blueprint $t) => $t->dropColumn(['status', 'file']));
        Schema::table('sites', fn (Blueprint $t) => $t->dropColumn('address_box_title'));
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('copy_from_id');
            $table->dropConstrainedForeignId('pricegrid_group_id');
            $table->dropColumn(['content_amp', 'pricegrid_type', 'pricegrid_header', 'rating_formula', 'hard_cache', 'rep_id', 'market_label', 'address_box_title']);
        });
    }
};
