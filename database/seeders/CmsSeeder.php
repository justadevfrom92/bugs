<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\Page;
use App\Models\Template;
use Illuminate\Database\Seeder;

/** Lando: templates, the site page tree and content blocks. */
class CmsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('templates') as $t) {
            Template::create(['name' => $t['name'], 'description' => $t['desc']]);
        }
        $templates = Template::pluck('id', 'name');

        foreach (DatabaseSeeder::data('pages') as $p) {
            Page::create([
                'path' => $p['path'], 'title' => $p['title'], 'template_id' => $templates[$p['template']] ?? null,
                'redirect' => $p['redirect'] ?: null, 'status' => $p['status'],
            ]);
        }

        foreach (DatabaseSeeder::data('blocks') as $b) {
            $block = ContentBlock::create(['name' => $b['name'], 'site' => $b['site'], 'html' => $b['html']]);
            $block->forceFill(['id' => $b['id'], 'updated_at' => $b['updated'], 'created_at' => $b['updated']])->save();
        }
    }
}
