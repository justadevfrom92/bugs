<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Page;
use App\Models\SiteBlock;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** Lando → Site: 30 days of website visits (documentation-range IPs, fictional) and some blocked IPs and areas. */
class SiteTrafficSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(92);
        $admin = User::where('email', 'admin@example.com')->value('id');
        $pages = Page::where('status', 'Published')->whereNull('redirect')->pluck('id', 'path');
        // Popular pages get most of the views
        $weighted = collect(['/' => 30, 'plans' => 14, 'byop' => 6, 'business' => 5, 'contact-us' => 4, 'freedom-flex' => 4, 'giddy-up' => 3, 'locations/houston' => 3, 'locations/dallas' => 3, 'checkout' => 4])
            ->filter(fn ($w, $p) => $pages->has($p))->flatMap(fn ($w, $p) => array_fill(0, $w, $p))
            ->merge($pages->keys()->reject(fn ($p) => str_starts_with($p, 'myaccount') || str_starts_with($p, 'checkout/') || (string) $p === '404'))->values();
        $customers = Customer::whereNotNull('password')->pluck('id')->merge(Customer::inRandomOrder()->limit(40)->pluck('id'))->unique()->values();
        $refs = [null, null, null, 'https://www.google.com/', 'https://www.google.com/', 'https://www.bing.com/', 'https://www.facebook.com/', 'https://www.powertochoose.org/'];
        $agents = ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) Safari/604.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) Safari/605.1', 'Mozilla/5.0 (Linux; Android 14) Chrome/128.0 Mobile'];

        // A pool of returning visitors
        $people = collect(range(1, 420))->map(fn ($i) => [
            'visitor' => Str::random(32), 'ip' => (mt_rand(0, 1) ? '198.51.100.' : '203.0.113.').mt_rand(1, 254),
            'customer' => mt_rand(1, 6) === 1 ? $customers->random() : null, 'agent' => $agents[array_rand($agents)],
        ])->reject(fn ($p) => $p['ip'] === '203.0.113.66');

        $rows = [];
        $add = function (array $p, string $path, Carbon $at, int $stay, bool $blocked = false, ?string $ref = null) use (&$rows, $pages) {
            $rows[] = ['visitor' => $p['visitor'], 'ip' => $p['ip'], 'path' => $path, 'page_id' => $pages[$path] ?? null, 'customer_id' => $p['customer'] ?? null,
                'referrer' => $ref, 'agent' => $p['agent'], 'blocked' => $blocked, 'created_at' => $at, 'seen_at' => $at->copy()->addSeconds($stay)];
        };
        for ($d = 29; $d >= 0; $d--) {
            $day = today()->subDays($d);
            $sessions = mt_rand(45, 80) + ($day->isWeekend() ? -15 : 0) + (29 - $d);   // growing a little
            for ($s = 0; $s < $sessions; $s++) {
                // Busier from mid-morning to evening
                $hour = [7, 8, 9, 10, 10, 11, 11, 12, 12, 13, 14, 14, 15, 16, 17, 18, 19, 19, 20, 21, 22, 23, 1, 6][mt_rand(0, 23)];
                $at = $day->copy()->setTime($hour, mt_rand(0, 59), mt_rand(0, 59));
                if ($at->isFuture()) {
                    continue;
                }
                $p = $people->random();
                $ref = $refs[array_rand($refs)];
                foreach (range(1, mt_rand(1, 5)) as $n) {
                    $path = $n === 1 ? $weighted->random() : ($p['customer'] && mt_rand(0, 1) ? ['myaccount/dashboard', 'myaccount/bills', 'myaccount/pay', 'myaccount/rewards'][mt_rand(0, 3)] : $weighted->random());
                    $stay = mt_rand(15, 240);
                    $add($p, $path, $at, $stay, false, $n === 1 ? $ref : null);
                    $at = $at->copy()->addSeconds($stay + mt_rand(2, 20));
                }
            }
        }
        // On the site right now
        foreach ($people->random(9) as $i => $p) {
            $at = now()->subMinutes(mt_rand(1, 25));
            $add($p, $i < 3 ? '/' : $weighted->random(), $at, (int) $at->diffInSeconds(now()->subSeconds(mt_rand(5, 120)), true));
        }

        // A scraper on the rates pages, a bot range probing for admin logins, and a contest page taken down
        $scraper = ['visitor' => Str::random(32), 'ip' => '203.0.113.66', 'customer' => null, 'agent' => 'python-requests/2.32'];
        foreach (range(1, 60) as $i) {
            $add($scraper, ['plans', 'business/plans', 'byop', 'historical-efls'][$i % 4], now()->subDays(3)->addMinutes($i), 2, $i > 40);
        }
        foreach (range(1, 36) as $i) {
            $bot = ['visitor' => Str::random(32), 'ip' => '192.0.2.'.mt_rand(1, 254), 'customer' => null, 'agent' => 'Mozilla/5.0 (compatible; scanner)'];
            $add($bot, ['wp-admin', 'wp-login.php', '.env', 'xmlrpc.php'][$i % 4], now()->subHours(mt_rand(1, 240)), 1, true);
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            SiteVisit::insert($chunk);
        }

        $block = fn (array $a) => SiteBlock::create($a + ['user_id' => $admin, 'created_at' => now()->subDays(mt_rand(4, 40))]);
        $block(['type' => 'ip', 'value' => '203.0.113.66', 'reason' => 'Scraping the plans and rates pages every few seconds', 'hits' => 20, 'last_hit_at' => now()->subDays(3)->addMinutes(60)]);
        $block(['type' => 'ip', 'value' => '192.0.2.*', 'reason' => 'Bot range probing for WordPress and .env files', 'hits' => 36, 'last_hit_at' => now()->subHour()]);
        $block(['type' => 'ip', 'value' => '198.51.100.250', 'reason' => 'Repeated fake sign-ups', 'expires_at' => now()->addDays(5), 'hits' => 4, 'last_hit_at' => now()->subDays(2)]);
        $block(['type' => 'ip', 'value' => '203.0.113.12', 'reason' => 'Contact form spam (resolved)', 'active' => false, 'hits' => 11, 'last_hit_at' => now()->subDays(20)]);
        $block(['type' => 'area', 'value' => 'wp-admin', 'reason' => 'Not a WordPress site: only bots ask for this', 'hits' => 9]);
        $block(['type' => 'area', 'value' => '.env', 'reason' => 'Bots looking for secrets', 'hits' => 9]);
        $block(['type' => 'area', 'value' => 'xmlrpc.php', 'reason' => 'Bots', 'hits' => 9]);
        $block(['type' => 'area', 'value' => 'careers/staff-accountant', 'reason' => 'Position filled', 'message' => 'This position has been filled. See our other openings on the Careers page.', 'active' => false]);
    }
}
