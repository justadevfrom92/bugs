<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\EmailCategory;
use App\Models\EmailSuppression;
use App\Models\EmailTemplate;
use App\Models\EmailTestSend;
use App\Models\MarketingChannel;
use App\Models\PromoCode;
use App\Models\Refund;
use App\Models\RewardOffer;
use App\Models\RewardRule;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Bounty offers and rules, Rodeo templates/channels/promo codes/survey/campaign, and sample Caboose refunds. */
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
        // Rodeo → Emails → Categories
        foreach ([
            ['Account', 'Welcome letters, confirmations and sign-in emails', '#00AEEF', ['Welcome Letter', 'Welcome Letter - Renewed', 'Renewal - Confirmation', 'Freedom Flex - Confirmation', 'MyAccount - Password Reset']],
            ['Billing', 'Bills, payments and collections notices', '#2e7d32', ['Bill Ready', 'Payment Made', 'Billing - Resi - Payment Made', 'Disconnect Notice']],
            ['Marketing', 'Offers and campaigns; only to opted-in customers', '#f39c12', ['Renewal Offer', 'Winback Offer']],
            ['Rewards', 'Peak Perks and Bounty emails', '#8e44ad', ['MyAcct - Peak Perks - Welcome to Peak Perks']],
            ['Surveys', 'Survey invitations', '#c0392b', ['Customer Survey']],
            ['Service', 'One-off messages from Customer Service', '#607d8b', ['Custom Message']],
        ] as $i => [$name, $desc, $color, $names]) {
            $cat = EmailCategory::create(['name' => $name, 'description' => $desc, 'color' => $color, 'position' => $i + 1]);
            EmailTemplate::whereIn('name', $names)->update(['email_category_id' => $cat->id]);
        }
        foreach ([['1001', 'Organic Website', 'Organic'], ['2001', 'Call Center', 'Agent'], ['3001', 'Google Ads Pay Per Click', 'Paid'],
            ['32001', 'Partner Referral Network', 'Partner'], ['41001', 'Broker - Sample Brokerage', 'Broker'], ['52001', 'Community Event', 'Partner']] as [$msid, $name, $type]) {
            MarketingChannel::create(['msid' => $msid, 'name' => $name, 'type' => $type]);
        }
        PromoCode::create(['code' => 'FALL25', 'description' => '$25 bill credit for fall sign-ups', 'credit' => 25, 'starts_on' => today()->subDays(20), 'ends_on' => today()->addDays(40)]);
        PromoCode::create(['code' => 'WELCOME', 'description' => 'Website welcome offer', 'credit' => 10]);

        $this->call(SurveySeeder::class);

        $admin = User::where('email', 'admin@example.com')->first();
        $tpl = EmailTemplate::where('name', 'Renewal Offer')->first();
        $campaign = Campaign::create(['name' => 'Fall Renewal Reminder', 'channel' => 'Email', 'email_template_id' => $tpl?->id, 'audience' => ['statuses' => ['Good - On Flow']],
            'status' => 'sent', 'sent_at' => now()->subDays(6), 'created_by' => $admin?->id]);
        $recipients = $campaign->audienceQuery()->limit(14)->get();
        foreach ($recipients as $i => $c) {
            // Two bounce, and their addresses go on the suppression list (Rodeo → Emails)
            $bounced = in_array($i, [4, 10], true) && $c->email && $c->account !== '1219000000';
            ContactLog::create(['customer_id' => $c->id, 'campaign_id' => $campaign->id, 'channel' => 'Email', 'template' => $tpl->name, 'status' => 'sent',
                'sent_at' => now()->subDays(6), 'dropped_at' => $bounced ? now()->subDays(6)->addMinutes(2) : null,
                'opened_at' => ! $bounced && $i % 2 ? now()->subDays(5) : null, 'clicked_at' => ! $bounced && $i % 4 === 1 ? now()->subDays(5) : null, 'created_at' => now()->subDays(6)]);
            if ($bounced) {
                EmailSuppression::create(['email' => $c->email, 'reason' => 'bounce', 'customer_id' => $c->id, 'note' => 'Mailbox does not exist (550)', 'created_at' => now()->subDays(6)]);
            }
        }
        foreach ([['unsubscribe', 'Clicked unsubscribe in a Renewal Offer email', 9], ['complaint', 'Marked a Winback Offer email as spam', 14], ['manual', 'Asked on the phone to stop all emails', 3]] as [$reason, $note, $days]) {
            $c = Customer::whereNotNull('email')->where('account', '!=', '1219000000')->whereNotIn('email', EmailSuppression::select('email'))->orderByDesc('id')->skip($days)->first();
            $c && EmailSuppression::create(['email' => $c->email, 'reason' => $reason, 'customer_id' => $c->id, 'note' => $note,
                'user_id' => $reason === 'manual' ? User::where('email', 'csr@example.com')->value('id') : null, 'created_at' => now()->subDays($days)]);
        }
        EmailSuppression::create(['email' => 'old-address@example.net', 'reason' => 'bounce', 'note' => 'Address from a closed account', 'created_at' => now()->subDays(40)]);
        $campaign->update(['recipients' => $recipients->count()]);
        Campaign::create(['name' => 'Peak Perks Enrollment', 'channel' => 'Email', 'email_template_id' => EmailTemplate::where('name', 'like', '%Peak Perks%')->value('id'),
            'audience' => ['statuses' => ['Good - On Flow'], 'without_product' => 'Peak Perks'], 'created_by' => $admin?->id]);

        // Caboose: a refund waiting for approval for an account with a credit balance
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
