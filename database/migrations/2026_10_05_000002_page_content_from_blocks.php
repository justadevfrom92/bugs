<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Page content areas pick predefined content (a content block) from a list
 * instead of holding their own editable HTML. Existing page text is moved
 * into content blocks in a "Page Content" category, so nothing is lost.
 */
return new class extends Migration
{
    private const AREAS = ['primary' => 'Primary', 'secondary' => 'Secondary', 'parent' => 'Parent', 'auxiliary' => 'Auxiliary', 'amp' => 'AMP'];

    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            foreach (array_keys(self::AREAS) as $area) {
                $table->foreignId('content_'.$area.'_id')->nullable()->constrained('content_blocks')->nullOnDelete();
            }
        });

        $category = null;
        foreach (DB::table('pages')->get() as $page) {
            foreach (self::AREAS as $area => $label) {
                $html = $page->{'content_'.$area};
                if ($html === null || trim($html) === '') {
                    continue;
                }
                $category ??= DB::table('block_categories')->where('name', 'Page Content')->value('id')
                    ?? DB::table('block_categories')->insertGetId(['name' => 'Page Content', 'created_at' => now(), 'updated_at' => now()]);
                $id = DB::table('content_blocks')->insertGetId([
                    'name' => $page->title.' - '.$label, 'slug' => 'page-'.$page->id.'-'.$area, 'html' => $html,
                    'category_id' => $category, 'site_id' => $page->site_id, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('pages')->where('id', $page->id)->update(['content_'.$area.'_id' => $id]);
            }
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(array_map(fn ($a) => 'content_'.$a, array_keys(self::AREAS)));
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            foreach (array_keys(self::AREAS) as $area) {
                $table->longText('content_'.$area)->nullable();
            }
        });
        foreach (array_keys(self::AREAS) as $area) {
            foreach (DB::table('pages')->whereNotNull('content_'.$area.'_id')->get() as $page) {
                DB::table('pages')->where('id', $page->id)->update(['content_'.$area => DB::table('content_blocks')->where('id', $page->{'content_'.$area.'_id'})->value('html')]);
            }
        }
        Schema::table('pages', function (Blueprint $table) {
            foreach (array_keys(self::AREAS) as $area) {
                $table->dropConstrainedForeignId('content_'.$area.'_id');
            }
        });
    }
};
