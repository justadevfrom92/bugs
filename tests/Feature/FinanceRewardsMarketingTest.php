<?php

namespace Tests\Feature;

use App\Mail\TemplateTest;
use App\Models\Campaign;
use App\Models\ContactLog;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RewardOffer;
use App\Models\RewardRule;
use App\Models\StarEntry;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Caboose (finance), Bounty (rewards), Rodeo (marketing) and the public survey page. */
class FinanceRewardsMarketingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email = 'admin@example.com'): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_every_menu_page_opens_and_menus_stay_inside_their_app(): void
    {
        $this->actingAs($this->user());
        foreach (['caboose', 'bounty', 'rodeo'] as $app) {
            foreach (config("admin.apps.$app.menu") as $links) {
                foreach ($links as $link) {
                    $html = $this->get(route($link[1]))->assertOk()->getContent();
                    foreach (['corral', 'lando', 'astro', 'walker', 'sheriff'] as $other) {
                        $this->assertStringNotContainsString('/admin/'.$other.'/', $html, "$app {$link[0]} links into $other");
                    }
                }
            }
        }
        $this->get(route('rodeo.campaigns.create'))->assertOk();
        $this->get(route('rodeo.campaigns.edit', Campaign::first()))->assertOk();
        $this->get(route('rodeo.templates.create'))->assertOk();
        $this->get(route('rodeo.templates.edit', EmailTemplate::first()))->assertOk();
        $this->get(route('rodeo.surveys.create'))->assertOk();
        $this->get(route('rodeo.surveys.edit', Survey::first()))->assertOk();
        $this->get(route('rodeo.surveys.results', Survey::first()))->assertOk()->assertSee('Net Promoter');
    }

    public function test_apps_follow_role_permissions(): void
    {
        $this->actingAs($this->user('csr@example.com'));
        $this->get('/admin/caboose')->assertRedirect(route('admin.launcher', ['denied' => 'caboose']));
        $this->get('/admin/rodeo')->assertRedirect(route('admin.launcher', ['denied' => 'rodeo']));
        $this->get('/admin/bounty')->assertOk();

        $this->actingAs($this->user('finance@example.com'));
        $this->get('/admin/caboose')->assertOk();
        $this->get('/admin/bounty')->assertRedirect(route('admin.launcher', ['denied' => 'bounty']));

        $this->actingAs($this->user('marketing@example.com'));
        $this->get('/admin/rodeo')->assertOk();
        $this->get('/admin/caboose')->assertRedirect(route('admin.launcher', ['denied' => 'caboose']));
    }

    public function test_caboose_payments_ledger_refunds_and_journal(): void
    {
        $this->actingAs($this->user());
        $c = Customer::where('status', 'Good - On Flow')->firstOrFail();
        $balance = (float) $c->balance;

        $this->post(route('caboose.payments.record'), ['account' => $c->account, 'amount' => 25, 'method' => 'Check', 'reference' => '1042', 'kind' => 'Balance Payment'])->assertRedirect();
        $this->assertEqualsWithDelta($balance - 25, (float) $c->fresh()->balance, 0.001);
        $payment = Payment::where('customer_id', $c->id)->latest('id')->firstOrFail();
        $this->post(route('caboose.payments.reverse', $payment))->assertRedirect();
        $this->assertEqualsWithDelta($balance, (float) $c->fresh()->balance, 0.001);

        $entry = LedgerEntry::create(['customer_id' => $c->id, 'kind' => 'credit', 'amount' => 10, 'description' => 'Goodwill', 'status' => 'pending']);
        $this->post(route('caboose.ledger.decide', $entry), ['do' => 'apply'])->assertRedirect();
        $this->assertSame('applied', $entry->fresh()->status);
        $this->assertEqualsWithDelta($balance - 10, (float) $c->fresh()->balance, 0.001);

        // Refund a credit balance: request → approve → paid
        $c->update(['balance' => -40]);
        $this->post(route('caboose.refunds.request'), ['account' => $c->account, 'kind' => 'balance', 'amount' => 50, 'reason' => 'Move out', 'method' => 'Check'])->assertSessionHasErrors();
        $this->post(route('caboose.refunds.request'), ['account' => $c->account, 'kind' => 'balance', 'amount' => 40, 'reason' => 'Move out', 'method' => 'Check'])->assertRedirect();
        $refund = Refund::where('customer_id', $c->id)->latest('id')->firstOrFail();
        $this->post(route('caboose.refunds.decide', $refund), ['do' => 'paid'])->assertStatus(422);
        $this->post(route('caboose.refunds.decide', $refund), ['do' => 'approve'])->assertRedirect();
        $this->post(route('caboose.refunds.decide', $refund), ['do' => 'paid'])->assertRedirect();
        $this->assertSame('paid', $refund->fresh()->status);
        $this->assertEqualsWithDelta(0, (float) $c->fresh()->balance, 0.001);

        $this->get(route('caboose.journal', ['format' => 'csv', 'start' => today()->subYear()->toDateString()]))->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
        $this->get(route('caboose.journal', ['format' => 'xlsx']))->assertOk();
    }

    public function test_refund_decisions_need_the_refunds_right(): void
    {
        $refund = Refund::where('status', 'requested')->first() ?? Refund::first();
        $refund->update(['status' => 'requested']);
        $role = $this->user('finance@example.com')->role;
        $role->update(['perms' => array_values(array_diff($role->perms, ['refunds']))]);
        $this->actingAs($this->user('finance@example.com')->fresh());
        $this->post(route('caboose.refunds.decide', $refund), ['do' => 'approve'])->assertForbidden();
    }

    public function test_bounty_rules_adjustments_and_fulfilment(): void
    {
        $this->actingAs($this->user());
        $rule = RewardRule::firstOrFail();
        $this->put(route('bounty.rules.save'), ['r' => [$rule->id => ['label' => 'Renamed rule', 'stars' => 7, 'active' => 1]]])->assertRedirect();
        $this->assertSame('Renamed rule', $rule->fresh()->label);

        $c = Customer::where('status', 'Good - On Flow')->firstOrFail();
        $stars = (int) $c->stars;
        $this->post(route('bounty.adjust', $c), ['stars' => 15, 'reason' => 'Goodwill'])->assertRedirect();
        $this->assertSame($stars + 15, (int) $c->fresh()->stars);
        $this->post(route('bounty.adjust', $c), ['stars' => -($stars + 1000), 'reason' => 'Too many'])->assertSessionHasErrors('stars');

        $redemption = StarEntry::create(['customer_id' => $c->id, 'reason' => 'Redeemed: Test gift card', 'stars' => -1, 'reward_offer_id' => RewardOffer::value('id'), 'fulfillment' => 'pending']);
        $this->get(route('bounty.redemptions'))->assertOk()->assertSee('Test gift card');
        $this->post(route('bounty.fulfil', $redemption))->assertRedirect();
        $this->assertSame('sent', $redemption->fresh()->fulfillment);
    }

    public function test_rodeo_campaign_template_and_survey(): void
    {
        $this->actingAs($this->user('marketing@example.com'));
        $this->post(route('rodeo.templates.store'), ['name' => 'Survey invite', 'subject' => 'Tell us', 'body' => '<p>Hi {{first_name}}, <a href="{{survey:how-are-we-doing}}">take our survey</a></p>'])->assertRedirect();
        $template = EmailTemplate::where('name', 'Survey invite')->firstOrFail();

        $this->post(route('rodeo.campaigns.store'), ['name' => 'Fall survey', 'channel' => 'Email', 'email_template_id' => $template->id, 'audience' => ['statuses' => ['Good - On Flow']]])->assertRedirect();
        $campaign = Campaign::where('name', 'Fall survey')->firstOrFail();
        $count = $campaign->audienceQuery()->count();
        $this->assertGreaterThan(0, $count);
        $this->post(route('rodeo.campaigns.send', $campaign))->assertRedirect()->assertSessionHas('status');
        $this->assertSame($count, ContactLog::where('campaign_id', $campaign->id)->count());

        $this->post(route('rodeo.surveys.store'), ['title' => 'Quick poll', 'status' => 'active', 'q' => [
            ['question' => 'How likely are you to recommend us?', 'type' => 'rating'],
            ['question' => 'Favorite thing?', 'type' => 'choice', 'options' => "Price\nService"],
        ]])->assertRedirect();
        $this->assertSame(2, Survey::where('title', 'Quick poll')->firstOrFail()->questions()->count());

        $this->post(route('rodeo.channels.save'), ['msid' => '77001', 'name' => 'Radio spot', 'type' => 'Paid'])->assertRedirect();
        $this->post(route('rodeo.promos.save'), ['code' => 'rodeo10', 'description' => '$10 off', 'credit' => 10])->assertRedirect();
        $this->assertDatabaseHas('promo_codes', ['code' => 'RODEO10']);
    }

    public function test_public_survey_ties_signed_links_to_the_customer(): void
    {
        $survey = Survey::where('slug', 'how-are-we-doing')->firstOrFail()->load('questions');
        $c = Customer::firstOrFail();
        $link = EmailTemplate::make(['body' => '{{survey:how-are-we-doing}}'])->render($c);
        $answers = $survey->questions->values()->map(fn ($q) => match ($q->type) {
            'rating' => 9, 'choice' => $q->options[0], default => 'Great'
        })->all();

        $this->get($link)->assertOk()->assertSee($survey->title)->assertSee('Answering as');
        $path = parse_url($link, PHP_URL_PATH).'?'.parse_url($link, PHP_URL_QUERY);
        $this->post($path, ['a' => $answers])->assertRedirect();
        $this->assertSame($c->id, $survey->responses()->latest('id')->first()->customer_id);

        // A tampered account number is not trusted
        $this->post('/survey/how-are-we-doing?c='.Customer::latest('id')->value('account'), ['a' => $answers])->assertRedirect();
        $this->assertNull($survey->responses()->latest('id')->first()->customer_id);
        $this->post('/survey/how-are-we-doing', ['a' => [42]])->assertSessionHasErrors();
        $this->get('/survey/how-are-we-doing')->assertOk()->assertDontSee('Answering as');
    }

    public function test_email_template_test_sends(): void
    {
        Mail::fake();
        $admin = $this->user();
        $this->actingAs($admin);
        $template = EmailTemplate::firstOrFail();
        $url = route('rodeo.templates.test', $template);
        $before = (int) $template->testSends()->max('id'); // the seed data has a sample test already
        $this->get(route('rodeo.templates.edit', $template))->assertOk()->assertSee('Send a Test')->assertSee('An admin team')->assertSee('mail log');

        // Typed addresses, filled in from a sample account
        $sample = Customer::firstOrFail();
        $this->post($url, ['mode' => 'emails', 'emails' => "a@example.com, b@example.com\na@example.com", 'sample' => $sample->account])->assertRedirect()->assertSessionHas('test_result');
        Mail::assertSentCount(2);
        Mail::assertSent(TemplateTest::class, fn ($m) => $m->hasTo('a@example.com') && str_starts_with($m->subjectLine, '[TEST] ') && str_contains($m->bodyHtml, e($sample->first_name)));
        $this->post($url, ['mode' => 'emails', 'emails' => 'not-an-email'])->assertSessionHasErrors('emails');

        // Account numbers: each customer gets their own fill-ins and a Contact Log entry
        $c = Customer::whereNotNull('email')->latest('id')->firstOrFail();
        $this->post($url, ['mode' => 'accounts', 'accounts' => $c->account])->assertRedirect();
        $this->assertDatabaseHas('contact_logs', ['customer_id' => $c->id, 'template' => 'Test: '.$template->name]);
        $this->post($url, ['mode' => 'accounts', 'accounts' => '999'])->assertSessionHasErrors('accounts');

        // Bookmarks, an admin team, a customer category
        $admin->bookmarks()->attach($c->id);
        $this->post($url, ['mode' => 'bookmarks'])->assertRedirect();
        $role = $admin->role;
        $this->post($url, ['mode' => 'team', 'role_id' => $role->id])->assertRedirect();
        $this->post($url, ['mode' => 'category', 'status' => 'Good - On Flow', 'limit' => 3])->assertRedirect();
        $this->post($url, ['mode' => 'category', 'limit' => 500])->assertSessionHasErrors('limit');

        $sends = $template->testSends()->where('id', '>', $before)->orderBy('id')->get();
        $this->assertSame(['emails', 'accounts', 'bookmarks', 'team', 'category'], $sends->pluck('mode')->all());
        $this->assertSame('logged', $sends->first()->status); // MAIL_MAILER=log in tests: written to the log, not delivered
        $this->assertSame(3, count($sends->last()->recipients));
        $this->get(route('rodeo.templates.edit', $template))->assertSee('Recent Tests')->assertSee('a@example.com');
    }
}
