<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\EmailTestSend;
use App\Models\MarketingChannel;
use App\Models\PromoCode;
use App\Models\Refund;
use App\Models\RewardOffer;
use App\Models\RewardRule;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Bounty offers and rules, Rodeo templates/channels/promo codes/survey/campaign, and sample Strongbox refunds. */
class MarketingRewardsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DatabaseSeeder::data('reward_offers') as $o) {
            RewardOffer::create($o);
        }
        foreach ([
            ['on_time_payment', 'Bill Payment', 0.05, true], ['loyalty_monthly', 'Monthly Loyalty', 10, false],
            ['autopay_monthly', 'AutoPay Bonus', 5, false], ['paperless_signup', 'Paperless Signup', 10, false], ['referral', 'Referral', 50, false],
        ] as [$key, $label, $stars, $perDollar]) {
            RewardRule::create(['key' => $key, 'label' => $label, 'stars' => $stars, 'per_dollar' => $perDollar]);
        }

        foreach (DatabaseSeeder::data('email_templates') as $t) {
            EmailTemplate::create($t);
        }
        foreach ([['1001', 'Organic Website', 'Organic'], ['2001', 'Call Center', 'Agent'], ['3001', 'Google Ads Pay Per Click', 'Paid'],
            ['32001', 'Partner Referral Network', 'Partner'], ['41001', 'Broker - Sample Brokerage', 'Broker'], ['52001', 'Community Event', 'Partner']] as [$msid, $name, $type]) {
            MarketingChannel::create(['msid' => $msid, 'name' => $name, 'type' => $type]);
        }
        PromoCode::create(['code' => 'FALL25', 'description' => '$25 bill credit for fall sign-ups', 'credit' => 25, 'starts_on' => today()->subDays(20), 'ends_on' => today()->addDays(40)]);
        PromoCode::create(['code' => 'WELCOME', 'description' => 'Website welcome offer', 'credit' => 10]);

        $survey = Survey::create(['title' => 'How are we doing?', 'slug' => 'how-are-we-doing', 'intro' => 'Two minutes to help us serve you better.']);
        $survey->questions()->createMany([
            ['question' => 'How likely are you to recommend us to a friend?', 'type' => 'rating', 'options' => ['min' => 0, 'max' => 10], 'position' => 0],
            ['question' => 'How did you hear about us?', 'type' => 'choice', 'options' => ['Search engine', 'Friend or family', 'Ad', 'Power to Choose', 'Other'], 'position' => 1],
            ['question' => 'Anything we could do better?', 'type' => 'text', 'options' => null, 'position' => 2],
        ]);
        foreach (Customer::where('status', 'Good - On Flow')->limit(9)->get() as $i => $c) {
            $survey->responses()->create(['customer_id' => $c->id, 'created_at' => now()->subDays($i + 1),
                'answers' => [(string) [10, 9, 9, 8, 10, 7, 6, 9, 10][$i], ['Search engine', 'Friend or family', 'Ad', 'Power to Choose', 'Search engine'][$i % 5], [0 => 'Faster answers on the phone.', 3 => 'Text me when my bill is ready.', 6 => 'Happy so far, the app is easy.'][$i] ?? '']]);
        }

        $admin = User::where('email', 'admin@example.com')->first();
        $tpl = EmailTemplate::where('name', 'Renewal Offer')->first();
        $campaign = Campaign::create(['name' => 'Fall Renewal Reminder', 'channel' => 'Email', 'email_template_id' => $tpl?->id, 'audience' => ['statuses' => ['Good - On Flow']],
            'status' => 'sent', 'sent_at' => now()->subDays(6), 'created_by' => $admin?->id]);
        $recipients = $campaign->audienceQuery()->limit(14)->get();
        foreach ($recipients as $i => $c) {
            ContactLog::create(['customer_id' => $c->id, 'campaign_id' => $campaign->id, 'channel' => 'Email', 'template' => $tpl->name, 'status' => 'sent',
                'sent_at' => now()->subDays(6), 'opened_at' => $i % 2 ? now()->subDays(5) : null, 'clicked_at' => $i % 4 === 1 ? now()->subDays(5) : null, 'created_at' => now()->subDays(6)]);
        }
        $campaign->update(['recipients' => $recipients->count()]);
        Campaign::create(['name' => 'Peak Perks Enrollment', 'channel' => 'Email', 'email_template_id' => EmailTemplate::where('name', 'like', '%Peak Perks%')->value('id'),
            'audience' => ['statuses' => ['Good - On Flow'], 'without_product' => 'Peak Perks'], 'created_by' => $admin?->id]);

        // Strongbox: a refund waiting for approval for an account with a credit balance
        $credit = Customer::where('balance', '<', 0)->first() ?? tap(Customer::where('status', 'Dropped - Churned')->first(), fn ($c) => $c?->update(['balance' => -42.18]));
        if ($credit) {
            Refund::create(['customer_id' => $credit->id, 'amount' => abs($credit->balance), 'reason' => 'Credit balance after final bill', 'method' => 'Check',
                'requested_by' => User::where('email', 'csr@example.com')->value('id')]);
        }
        Customer::where('status', 'Pending - Deposit Due')->update(['deposit_due' => 150]);

        // A test send of the welcome email to the marketing team
        $marketing = User::where('email', 'marketing@example.com')->first();
        if ($marketing && $welcome = EmailTemplate::orderBy('id')->first()) {
            EmailTestSend::create(['email_template_id' => $welcome->id, 'user_id' => $marketing->id, 'mode' => 'team', 'target' => $marketing->role?->name.' team',
                'recipients' => [['email' => $marketing->email, 'name' => $marketing->name, 'status' => 'logged']], 'status' => 'logged', 'created_at' => now()->subDay()]);
        }
    }
}
