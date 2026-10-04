<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Fields from the original Lando/Astro edit screens that the first build left out. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->string('type')->default('TDSP');
            $table->string('state', 2)->default('TX');
            $table->string('commodity')->default('Electric');
            $table->string('units')->default('kWh');
            $table->string('duns_number')->nullable();
            $table->string('edi_name')->nullable();
            $table->string('status')->default('Active');   // Active | Inactive
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('html_title')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('promo_code')->nullable();
            $table->boolean('no_index')->default(false);   // left out of the sitemap, robots noindex
        });

        Schema::table('templates', function (Blueprint $table) {
            $table->string('file')->nullable();            // the layout file in public/ the template uses
        });

        // Astro → BYOP Products: allowed discount range for each add-on (¢/kWh)
        Schema::table('byop_products', function (Blueprint $table) {
            $table->decimal('min_discount', 6, 2)->nullable();
            $table->decimal('max_discount', 6, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('markets', fn (Blueprint $t) => $t->dropColumn(['type', 'state', 'commodity', 'units', 'duns_number', 'edi_name', 'status']));
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn(['html_title', 'meta_keywords', 'promo_code', 'no_index']));
        Schema::table('templates', fn (Blueprint $t) => $t->dropColumn('file'));
        Schema::table('byop_products', fn (Blueprint $t) => $t->dropColumn(['min_discount', 'max_discount']));
    }
};
