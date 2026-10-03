<?php

namespace Tests\Feature;

use App\Models\ByopProduct;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Market;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email = 'admin@example.com'): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/corral')->assertRedirect('/admin/login');
    }

    public function test_sign_in_with_the_seeded_password_and_reject_bad_or_disabled_accounts(): void
    {
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/login', ['email' => 'former@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->user());
    }

    public function test_every_admin_screen_renders_for_an_administrator(): void
    {
        $this->actingAs($this->user());
        $customer = Customer::first();
        $plan = Plan::first();

        $urls = ['/admin', '/admin/corral', '/admin/corral?q=houston', '/admin/corral?exception=*&limit=0',
            '/admin/corral/customers/'.$customer->account, '/admin/corral/esiid?q=Example', '/admin/corral/orders/create',
            '/admin/corral/orders/create-biz', '/admin/corral/orders/create?renew='.$customer->account,
            '/admin/corral/reports/orders', '/admin/corral/reports/orders?run=1&output=summary', '/admin/corral/queues',
            '/admin/corral/queues/unapplied-payments', '/admin/corral/messages',
            '/admin/lando/pages', '/admin/lando/pages/create', '/admin/lando/pages/1/edit', '/admin/lando/templates',
            '/admin/lando/blocks', '/admin/lando/blocks/create', '/admin/lando/blocks/8/edit', '/admin/lando/plans',
            '/admin/lando/plans/create', '/admin/lando/plans/'.$plan->id.'/edit', '/admin/lando/groups',
            '/admin/lando/rates', '/admin/lando/rates?plan=JEY&kwh=500', '/admin/lando/rates/edit', '/admin/lando/fees', '/admin/lando/markets',
            '/admin/astro/terms', '/admin/astro/etfs', '/admin/astro/products',
            '/admin/sheriff/users', '/admin/sheriff/roles', '/admin/sheriff/integrations', '/admin/sheriff/jobs'];
        foreach (array_keys(config('admin.reference_tables')) as $table) {
            $urls[] = '/admin/sheriff/data/'.$table;
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_roles_limit_which_apps_a_user_can_open(): void
    {
        $this->actingAs($this->user('pricing@example.com'));

        $this->get('/admin/lando/plans')->assertOk();
        $this->get('/admin/astro/terms')->assertOk();
        $this->get('/admin/corral')->assertRedirect('/admin?denied=corral');
        $this->get('/admin/sheriff/users')->assertRedirect('/admin?denied=sheriff');
        $this->get('/admin')->assertSee('No access');
    }

    public function test_refunds_and_deletes_need_their_rights(): void
    {
        $payment = Payment::where('status', 'Success')->first();

        $this->actingAs($this->user('csr@example.com'))
            ->post('/admin/corral/payments/'.$payment->id.'/reverse')->assertForbidden();

        $before = $payment->customer->balance;
        $this->actingAs($this->user())->post('/admin/corral/payments/'.$payment->id.'/reverse')->assertRedirect();
        $this->assertSame('Reversed', $payment->fresh()->status);
        $this->assertEqualsWithDelta($before + $payment->amount, $payment->customer->fresh()->balance, 0.001);
    }

    public function test_rate_changes_reach_the_website_catalog_on_their_effective_date(): void
    {
        $this->actingAs($this->user());
        $plan = Plan::where('internal', 'CT12')->first();
        $oncor = Market::where('name', 'TX-E-ONCOR')->first();

        $this->put('/admin/lando/rates', ['effective_on' => now()->addDay()->toDateString(), 'rates' => [$plan->id => [$oncor->id => '9.999']]])->assertRedirect();
        $this->get('/shared/config/catalog.js')->assertOk()->assertDontSee('"energy":9.999', false);

        $this->put('/admin/lando/rates', ['effective_on' => today()->toDateString(), 'rates' => [$plan->id => [$oncor->id => '5.000']]])->assertRedirect();
        $this->get('/shared/config/catalog.js')->assertSee('"plan":"CT12","market":"TX-E-ONCOR","energy":5', false);
    }

    public function test_hidden_byop_products_and_plan_edits_show_in_the_catalog(): void
    {
        $this->actingAs($this->user());
        $product = ByopProduct::where('key', 'peakperks')->first();
        $plan = Plan::where('internal', 'G18')->first();

        $this->put('/admin/astro/products', ['p' => [$product->id => ['rate_adj' => '-0.5', 'monthly' => '0', 'active' => '0']]])->assertRedirect();
        $this->put('/admin/lando/plans/'.$plan->id, [
            'name' => 'The Gruene 18', 'internal' => 'G18', 'slug' => '', 'active' => 1, 'type' => 'Resi', 'term' => 18,
            'rolloff' => 'JEY3', 'etf' => '$275', 'mrc' => 4.95, 'green' => 100, 'tags' => 'fixed, green', 'perks' => "Solar from West Texas\nFixed for 18 months",
        ])->assertRedirect('/admin/lando/plans');

        $this->get('/shared/config/catalog.js')
            ->assertSee('"key":"peakperks","name":"Peak Perks","model":"ItemProductPeakPerk_model","type":"Discount","adj":-0.5,"monthly":0,"step":"Lower Your Bill","active":false', false)
            ->assertSee('Solar from West Texas');
    }

    public function test_phone_orders_create_an_account_and_queue_item(): void
    {
        $this->actingAs($this->user());
        $plan = Plan::where('internal', 'PP12')->first();

        $this->post('/admin/corral/orders', [
            'biz' => 0, 'name' => 'Test Person', 'phone' => '(555) 555-0199', 'email' => 'test@example.com',
            'address' => '12 Test St', 'city' => 'Houston', 'zip' => '77002', 'esiid' => '', 'market_id' => Market::first()->id,
            'plan_id' => $plan->id, 'enrollment_type' => 'Switch', 'autopay' => 1, 'paperless' => 0, 'peak_perks' => 0, 'source' => 'Phone',
        ])->assertRedirect();

        $c = Customer::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Submitted', $c->status);
        $this->assertSame('No ESIID', $c->exception);
        $this->assertTrue(WorkItem::open()->where('queue', 'unprocessed-orders')->where('customer_id', $c->id)->exists());
    }

    public function test_website_contact_form_lands_in_corral(): void
    {
        $this->postJson('/contact', ['first' => 'Pat', 'last' => 'Example', 'email' => 'pat@example.com', 'topic' => 'general', 'message' => 'Hello'])
            ->assertCreated();
        $this->assertSame(1, ContactMessage::open()->count());

        $this->actingAs($this->user())->get('/admin/corral/messages')->assertSee('Pat Example');
    }

    public function test_csv_report_downloads(): void
    {
        $this->actingAs($this->user())
            ->get('/admin/corral/reports/orders?run=1&output=csv&start=2026-01-01&end='.today()->toDateString())
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_users_cannot_lock_themselves_out(): void
    {
        $admin = $this->user();
        $this->actingAs($admin)->patch('/admin/sheriff/users/'.$admin->id, ['active' => 0])->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->active);
    }

    public function test_website_pages_and_data_load(): void
    {
        $this->get('/')->assertOk();
        $this->get('/shared/config/brand.js')->assertOk()->assertSee('ET.brand');
        $this->getJson('/site/session')->assertOk()->assertJsonStructure(['csrf', 'user']);
    }
}
