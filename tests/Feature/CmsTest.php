<?php

namespace Tests\Feature;

use App\Models\ByopProduct;
use App\Models\Market;
use App\Models\Page;
use App\Models\Plan;
use App\Models\PlanGroup;
use App\Models\Template;
use App\Models\User;
use App\Support\AdminMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Lando, Astro and Sheriff pages added to match the original menus. */
class CmsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_every_menu_link_in_every_app_opens(): void
    {
        $this->actingAs($this->admin());
        foreach (array_keys(config('admin.apps')) as $app) {
            // Build the menu as it would appear inside that app
            $this->get(route(config("admin.apps.$app.home")))->assertOk();
            foreach (AdminMenu::sections($app) as $heading => $items) {
                foreach ($items as $it) {
                    $this->get($it['url'])->assertOk();
                }
            }
        }
        $this->get('/admin/lando/rates/edit?plan='.Plan::first()->id)->assertOk()->assertSee('Update Rates: ');
        $this->get('/admin/lando/rates/edit?all=1')->assertOk()->assertSee('Hide inactive plans');
        $this->get('/admin/sheriff/integrations/nope')->assertNotFound();
    }

    public function test_astro_menu_hides_lando_links_for_roles_without_lando(): void
    {
        $user = $this->admin();
        $user->role->update(['perms' => ['astro']]);
        $this->actingAs($user->fresh())->get('/admin/astro/terms')->assertOk()
            ->assertDontSee(route('lando.pages.index'))->assertSee(route('astro.byop.upload'));
    }

    public function test_templates_markets_groups_and_page_settings(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/lando/templates', ['name' => 'spring-campaign', 'description' => 'Campaign landing page'])->assertRedirect('/admin/lando/templates');
        $t = Template::where('name', 'spring-campaign')->firstOrFail();
        $this->get('/admin/lando/templates/'.$t->id.'/edit')->assertOk();
        $this->put('/admin/lando/templates/'.$t->id, ['name' => 'spring-campaign', 'description' => 'x', 'file' => '../../.env'])->assertSessionHasErrors('file');

        $this->post('/admin/lando/markets', ['name' => 'TX-E-SHARYLAND', 'short' => 'Sharyland', 'description' => 'Sharyland Utilities', 'region' => 'South',
            'type' => 'TDSP', 'state' => 'TX', 'commodity' => 'Electric', 'units' => 'kWh', 'status' => 'Active'])->assertRedirect();
        $m = Market::where('name', 'TX-E-SHARYLAND')->firstOrFail();
        $this->get('/admin/lando/markets/'.$m->id.'/edit')->assertOk()->assertSee('Sharyland');
        $this->post('/admin/lando/markets', ['name' => 'bad name'])->assertSessionHasErrors(['name', 'short']);

        [$a, $b] = Plan::where('active', true)->take(2)->pluck('id')->all();
        $this->post('/admin/lando/groups', ['name' => 'Spring Promo', 'slug' => 'spring-promo', 'plans' => [$b, $a]])->assertRedirect('/admin/lando/groups');
        $g = PlanGroup::where('slug', 'spring-promo')->firstOrFail();
        $this->assertSame([$b, $a], $g->plans()->orderBy('plan_group_plan.position')->pluck('plans.id')->all());
        $this->put('/admin/lando/groups/'.$g->id, ['name' => 'Spring', 'slug' => 'spring-promo', 'plans' => [$a]])->assertRedirect();
        $this->assertSame([$a], $g->plans()->pluck('plans.id')->all());

        $page = Page::where('status', 'Published')->where('path', '!=', '/')->firstOrFail();
        $this->put('/admin/lando/pages/'.$page->id, ['title' => $page->title, 'path' => $page->path, 'status' => 'Published', 'no_index' => '1', 'html_title' => 'SEO Title'])->assertRedirect();
        $this->assertTrue($page->fresh()->no_index);
        $this->get('/admin/lando/pages/'.$page->id.'/edit')->assertOk()->assertSee('SEO Title')->assertSee('Edits');
    }

    public function test_byop_discounts_upload_is_all_or_nothing(): void
    {
        $this->actingAs($this->admin());
        $p = ByopProduct::firstOrFail();
        $before = $p->rate_adj;

        $bad = UploadedFile::fake()->createWithContent('d.csv', "key,rate_adj,min_discount,max_discount\n{$p->key},-0.25,0,-0.5\nnot-a-product,1\n");
        $this->post('/admin/astro/uploads/byop', ['file' => $bad])->assertSessionHasErrors('file');
        $this->assertEquals($before, $p->fresh()->rate_adj);

        $good = UploadedFile::fake()->createWithContent('d.csv', "key,rate_adj,min_discount,max_discount\n{$p->key},-0.25,0,-0.5\n");
        $this->post('/admin/astro/uploads/byop', ['file' => $good])->assertRedirect('/admin/astro/products');
        $p->refresh();
        $this->assertEquals([-0.25, 0.0, -0.5], [(float) $p->rate_adj, (float) $p->min_discount, (float) $p->max_discount]);
    }

    public function test_sheriff_sitemap_and_tdsp_fees(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/sheriff/sitemap')->assertRedirect();
        $this->get('/admin/sheriff/sitemap')->assertOk()->assertSee('sitemap.xml');
        $this->get('/admin/sheriff/fees')->assertOk()->assertSee(route('sheriff.fees.update'));
        $this->get('/admin/sheriff/integrations/stripe')->assertOk()->assertSee('STRIPE_SECRET')->assertDontSee((string) config('admin.integrations.stripe.env.STRIPE_SECRET') ?: 'unset-value-guard');
    }
}
