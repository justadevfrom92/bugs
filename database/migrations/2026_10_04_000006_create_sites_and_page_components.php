<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Lando's website side, as in the original: several websites, each with its
 * own pages; HTML widgets (content blocks, in categories) that can be placed on
 * pages as components or with [[block|slug]] codes; and page content areas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();      // host name the site answers on, e.g. www.example.com
            $table->string('phone')->nullable();       // overrides the brand phone on this site's pages
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        Schema::create('block_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('content_blocks', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();          // null = every site
            $table->foreignId('category_id')->nullable()->constrained('block_categories')->nullOnDelete();
        });

        // The old free-text site column is replaced by site_id (null = every site)
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->dropColumn('site');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('file')->nullable();        // built-in pages served from a file in public/, e.g. plans.html
            $table->string('page_title')->nullable();
            $table->longText('content_primary')->nullable();
            $table->longText('content_secondary')->nullable();
            $table->longText('content_parent')->nullable();
            $table->longText('content_auxiliary')->nullable();
            $table->text('head_content')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('canonical')->default(true);
            $table->foreignId('market_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['path']);
            $table->unique(['site_id', 'path']);
        });

        Schema::create('page_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_block_id')->constrained()->cascadeOnDelete();
            $table->string('zone');                   // top | main | sidebar | bottom (config/cms.php)
            $table->unsignedSmallInteger('position')->default(0);
        });

        // Existing installs: everything so far belongs to the main site
        if (DB::table('pages')->exists() || DB::table('content_blocks')->exists()) {
            $siteId = DB::table('sites')->insertGetId([
                'name' => config('brand.name'), 'domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('pages')->update(['site_id' => $siteId]);
            foreach (DB::table('content_blocks')->get(['id', 'name']) as $b) {
                DB::table('content_blocks')->where('id', $b->id)->update(['slug' => Str::slug($b->name).'-'.$b->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_components');
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'path']);
            $table->dropConstrainedForeignId('market_id');
            $table->dropConstrainedForeignId('site_id');
            $table->dropColumn(['file', 'page_title', 'content_primary', 'content_secondary', 'content_parent', 'content_auxiliary', 'head_content', 'phone', 'canonical']);
            $table->unique('path');
        });
        Schema::table('content_blocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('site_id');
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
            $table->string('site')->default('');
        });
        Schema::dropIfExists('block_categories');
        Schema::dropIfExists('sites');
    }
};
