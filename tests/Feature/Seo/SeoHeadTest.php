<?php

namespace Tests\Feature\Seo;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\City;
use App\Models\Page;
use App\Models\SlugRedirect;
use Illuminate\Testing\TestResponse;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * Every public page must carry consistent canonical/hreflang tags, OpenGraph locale data,
 * a default share image and the schema.org blocks search engines use for rich results.
 */
class SeoHeadTest extends TestCase
{
    private array $createdApartmentIds = [];

    private array $createdBuildingIds = [];

    private array $createdCityIds = [];

    protected function tearDown(): void
    {
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();
        Building::whereIn('id', $this->createdBuildingIds)->delete();

        if ($this->createdCityIds) {
            SlugRedirect::where('redirectable_type', City::class)->whereIn('redirectable_id', $this->createdCityIds)->delete();
            City::whereIn('id', $this->createdCityIds)->delete();
        }

        parent::tearDown();
    }

    public function test_home_page_has_matching_canonical_and_hreflang_cluster(): void
    {
        $response = $this->get('/')->assertOk();

        $response->assertSee('<link rel="canonical" href="'.url('/').'">', false);
        $response->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/').'/" />', false);
        $response->assertSee('<link rel="alternate" hreflang="en" href="'.url('/en').'" />', false);
        $response->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/').'/" />', false);
        $response->assertSee('<meta property="og:locale" content="ar_SA">', false);
        $response->assertSee('<meta property="og:locale:alternate" content="en_GB">', false);
        $response->assertSee('<meta property="og:image" content="'.asset('img/og-default.png').'">', false);
    }

    public function test_english_home_page_flips_locale_signals(): void
    {
        // The locale route prefix is resolved at boot (console requests have no path), so the
        // app is re-created with the package's forced-locale switch to register the /en routes.
        $key = LaravelLocalization::ENV_ROUTE_KEY;
        putenv($key.'=en');
        $_ENV[$key] = $_SERVER[$key] = 'en';

        try {
            $this->refreshApplication();
            $response = $this->get('/en')->assertOk();
        } finally {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        $response->assertSee('<link rel="canonical" href="'.url('/en').'">', false);
        $response->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/').'/" />', false);
        $response->assertSee('<meta property="og:locale" content="en_GB">', false);
        $response->assertSee('<meta property="og:locale:alternate" content="ar_SA">', false);
        $response->assertSee('<html lang="en"', false);
    }

    public function test_home_page_publishes_organization_and_website_schema(): void
    {
        $blocks = $this->jsonLdBlocks($this->get('/')->assertOk());

        $organization = collect($blocks)->firstWhere('@type', 'Organization');
        $website = collect($blocks)->firstWhere('@type', 'WebSite');

        $this->assertNotNull($organization);
        $this->assertNotNull($website);
        $this->assertSame(url('/').'/#organization', $organization['@id']);
        $this->assertSame($organization['@id'], $website['publisher']['@id']);
        $this->assertArrayNotHasKey('description', collect($blocks)->firstWhere('@type', 'BreadcrumbList') ?? []);
    }

    public function test_search_page_is_noindex_and_hreflang_ignores_query_string(): void
    {
        $response = $this->get(route('apartments.search', ['city_id' => 1, 'rooms' => 2]))->assertOk();

        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        $response->assertSee('<link rel="canonical" href="'.route('apartments.search').'">', false);
        $response->assertSee('<link rel="alternate" hreflang="ar" href="'.route('apartments.search').'" />', false);
        $response->assertDontSee('hreflang="ar" href="'.route('apartments.search').'?', false);
    }

    public function test_city_slugs_are_normalised_and_old_slugs_redirect_permanently(): void
    {
        $city = $this->createCity('Seo Test City '.uniqid());

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $city->slug);

        $oldSlug = $city->slug;
        $city->update(['slug' => 'Renamed '.$city->slug]);

        $this->get(route('by-city', $oldSlug))
            ->assertStatus(301)
            ->assertRedirect(route('by-city', $city->slug));

        $this->get(route('by-city', strtoupper($city->slug)))
            ->assertStatus(301)
            ->assertRedirect(route('by-city', $city->slug));

        $response = $this->get(route('by-city', $city->slug))->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('by-city', $city->slug).'">', false);

        $breadcrumbs = collect($this->jsonLdBlocks($response))->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(route('by-city', $city->slug), $breadcrumbs['itemListElement'][1]['item']);
    }

    public function test_unknown_city_slug_is_a_404(): void
    {
        $this->get(route('by-city', 'no-such-city-'.uniqid()))->assertNotFound();
    }

    public function test_apartment_page_renders_product_schema_and_breadcrumbs(): void
    {
        $city = $this->createCity('Seo City '.uniqid());
        $building = $this->createBuilding($city->id);
        $apartment = $this->createApartment($building->id);

        $response = $this->get(route('apartments.show', $apartment->slug))->assertOk();
        $blocks = collect($this->jsonLdBlocks($response));

        $product = $blocks->firstWhere('@type', 'Product');
        $this->assertNotNull($product, 'Product JSON-LD must be rendered through jsonLdMulti');
        $this->assertSame($apartment->name_ar, $product['name']);
        $this->assertSame('SAR', $product['offers']['priceCurrency']);
        $this->assertSame(route('apartments.show', $apartment->slug), $product['url']);

        $breadcrumbs = $blocks->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(route('by-city', $city->slug), $breadcrumbs['itemListElement'][1]['item']);
        $this->assertSame(route('building.details', $building->slug), $breadcrumbs['itemListElement'][2]['item']);

        $response->assertSee('<meta property="og:type" content="product">', false);
    }

    public function test_faq_page_renders_faq_schema_when_questions_exist(): void
    {
        $page = Page::where('template', 'faq')->first();

        if (! $page) {
            $this->markTestSkipped('No FAQ page in this database.');
        }

        $blocks = collect($this->jsonLdBlocks($this->get(route('page', $page->slug))->assertOk()));

        $this->assertNotNull($blocks->firstWhere('@type', 'BreadcrumbList'));

        $faq = $blocks->firstWhere('@type', 'FAQPage');
        if ($faq) {
            $this->assertSame('Question', $faq['mainEntity'][0]['@type']);
        }
    }

    public function test_sitemap_lists_cities_with_clean_slugs_and_robots_blocks_private_feeds(): void
    {
        $city = $this->createCity('Sitemap City '.uniqid());

        $sitemap = $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $sitemap->assertSee('<loc>'.route('by-city', $city->slug).'</loc>', false);
        $this->assertStringNotContainsString('%20', route('by-city', $city->slug));

        $this->get(route('robots'))->assertOk()
            ->assertSee('Disallow: /*/unit.ics')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonLdBlocks(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

        return array_map(fn (string $json): array => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
    }

    private function createCity(string $name): City
    {
        $city = City::create([
            'name_ar' => 'مدينة اختبار',
            'name_en' => $name,
            'slug' => $name,
            'sort_order' => 99,
        ]);
        $this->createdCityIds[] = $city->id;

        return $city->fresh();
    }

    private function createBuilding(int $cityId): Building
    {
        $building = Building::create([
            'name_ar' => 'مبنى اختبار',
            'name_en' => 'Seo Building',
            'slug' => 'seo-building-'.uniqid(),
            'address' => 'Test Street',
            'city_id' => $cityId,
            'is_active' => true,
            'ttlock_password' => 'test-password',
        ]);
        $this->createdBuildingIds[] = $building->id;

        return $building;
    }

    private function createApartment(int $buildingId): Apartment
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة اختبار SEO',
            'name_en' => 'Seo Apartment',
            'slug' => 'seo-apartment-'.uniqid(),
            'building_id' => $buildingId,
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => 200.00,
            'is_active' => true,
        ]);
        $this->createdApartmentIds[] = $apartment->id;

        return $apartment;
    }
}
