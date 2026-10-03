<?php

namespace Database\Seeders;

use App\Models\ByopProduct;
use App\Models\Market;
use App\Models\Plan;
use App\Models\PlanGroup;
use App\Models\Rate;
use App\Models\TdspFee;
use App\Models\TermDiscount;
use App\Models\TermEtf;
use App\Models\ZipRange;
use Illuminate\Database\Seeder;

/** Markets, TDSP fees, plans, plan groups, rates and Astro pricing modifiers. Rates and fees are illustrative. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('markets') as $m) {
            Market::create(['name' => $m['name'], 'short' => $m['short'], 'description' => $m['desc'], 'region' => $m['region'], 'phone' => $m['phone']]);
        }
        $markets = Market::pluck('id', 'name');

        foreach (DatabaseSeeder::data('zip_ranges') as [$from, $to, $market]) {
            ZipRange::create(['zip_from' => $from, 'zip_to' => $to, 'market_id' => $markets[$market]]);
        }

        foreach (DatabaseSeeder::data('tdsp_fees') as $f) {
            TdspFee::create(['market_id' => $markets[$f['market']], 'per_kwh' => $f['perKwh'], 'per_bill' => $f['perBill'], 'effective_on' => $f['kwhDate']]);
        }

        foreach (DatabaseSeeder::data('plans') as $p) {
            Plan::create([
                'type' => $p['type'], 'term' => $p['term'], 'name' => $p['name'], 'internal' => $p['internal'],
                'slug' => $p['slug'], 'rolloff' => $p['rolloff'], 'etf' => $p['etf'], 'mrc' => $p['mrc'],
                'green' => $p['green'], 'active' => $p['active'], 'tags' => $p['tags'], 'perks' => $p['perks'],
            ]);
        }
        $plans = Plan::pluck('id', 'internal');

        foreach (DatabaseSeeder::data('plan_groups') as $g) {
            $group = PlanGroup::create(['name' => $g['name'], 'slug' => $g['slug']]);
            foreach ($g['plans'] as $i => $code) {
                $group->plans()->attach($plans[$code], ['position' => $i]);
            }
        }

        foreach (DatabaseSeeder::data('rates') as $r) {
            Rate::create(['plan_id' => $plans[$r['plan']], 'market_id' => $markets[$r['market']], 'energy' => $r['energy'], 'effective_on' => $r['effective']]);
        }

        $regions = DatabaseSeeder::data('regions');
        foreach (DatabaseSeeder::data('term_mods') as $row) {
            foreach ($regions as $region) {
                TermDiscount::create(['term' => $row['term'], 'region' => $region, 'discount' => $row[$region]]);
            }
            TermEtf::create(['term' => $row['term'], 'amount' => $row['etf']]);
        }

        foreach (DatabaseSeeder::data('byop_products') as $i => $p) {
            ByopProduct::create([
                'key' => $p['key'], 'name' => $p['name'], 'model' => $p['model'], 'type' => $p['type'], 'step' => $p['step'],
                'rate_adj' => $p['adj'], 'monthly' => $p['monthly'], 'active' => $p['active'], 'position' => $i,
            ]);
        }
    }
}
