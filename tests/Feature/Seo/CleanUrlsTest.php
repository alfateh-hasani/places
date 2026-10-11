<?php

namespace Tests\Feature\Seo;

use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use App\Models\City;
use App\Models\SlugRedirect;
use Tests\TestCase;

/**
 * Every public page answers 200 on exactly one lowercase, hyphenated URL; every other spelling
 * or legacy path is a permanent redirect to it.
 */
class CleanUrlsTest extends TestCase
{
    private array $createdApartmentIds = [];

    private array $createdBuildingIds = [];

    private array $createdCityIds = [];

    private array $createdBlogIds = [];

    protected function tearDown(): void
    {
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();
        Building::whereIn('id', $this->createdBuildingIds)->delete();
        Blog::whereIn('id', $this->createdBlogIds)->delete();
        City::whereIn('id', $this->createdCityIds)->delete();
        SlugRedirect::where(fn ($q) => $q
            ->where(fn ($q) => $q->where('redirectable_type', Apartment::class)->whereIn('redirectable_id', $this->createdApartmentIds))
            ->orWhere(fn ($q) => $q->where('redirectable_type', Building::class)->whereIn('redirectable_id', $this->createdBuildingIds))
            ->orWhere(fn ($q) => $q->where('redirectable_type', Blog::class)->whereIn('redirectable_id', $this->createdBlogIds))
            ->orWhere(fn ($q) => $q->where('redirectable_type', City::class)->whereIn('redirectable_id', $this->createdCityIds))
        )->delete();

        parent::tearDown();
    }

    public function test_explicit_default_locale_prefix_redirects_to_the_bare_path(): void
    {
        $this->get('/ar')->assertStatus(301)->assertRedirect(url('/').'/');
        $this->get('/ar/faq?x=1')->assertStatus(301)->assertRedirect(url('/faq?x=1'));
    }

    public function test_upper_case_locale_prefix_and_trailing_slashes_redirect(): void
    {
        $this->get('/EN')->assertStatus(301)->assertRedirect(url('/en'));
        $this->get('/En/faq')->assertStatus(301)->assertRedirect(url('/en/faq'));
        // Trailing slashes are also redirected, but the test client strips them before the request.
    }

    public function test_post_requests_and_api_paths_are_left_alone(): void
    {
        $this->assertNotSame(301, $this->post('/ar/contact-us')->getStatusCode());
        $this->get('/api/does-not-exist')->assertNotFound();
    }

    public function test_legacy_city_and_search_paths_redirect_permanently(): void
    {
        $city = $this->createCity();

        $this->get('/city/'.$city->slug.'/apartments')->assertStatus(301)->assertRedirect(route('by-city', $city->slug));
        $this->get('/apartments-filter?city_id='.$city->id.'&rooms=2')->assertStatus(301)->assertRedirect(route('apartments.search').'?city_id='.$city->id.'&rooms=2');
        $this->get('/apartments')->assertStatus(301);

        $this->assertSame(url('/city/'.$city->slug), route('by-city', $city->slug));
        $this->assertSame(url('/search'), route('apartments.search'));
    }

    public function test_search_accepts_a_city_slug(): void
    {
        $city = $this->createCity();
        $otherCity = $this->createCity();
        $inCity = $this->createApartment($this->createBuilding($city->id)->id);
        $elsewhere = $this->createApartment($this->createBuilding($otherCity->id)->id);

        $this->get(route('apartments.search', ['city' => strtoupper($city->slug)]))
            ->assertOk()
            ->assertSee('/apartments/'.$inCity->slug)
            ->assertDontSee('/apartments/'.$elsewhere->slug);
    }

    public function test_listing_slugs_are_normalised_and_other_spellings_redirect(): void
    {
        $city = $this->createCity();
        $building = $this->createBuilding($city->id, 'Seo_Tower '.uniqid());
        $apartment = $this->createApartment($building->id, 'A1'.strtoupper(uniqid()));

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $building->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $apartment->slug);

        $this->get('/building/'.strtoupper($building->slug))->assertStatus(301)->assertRedirect(route('building.details', $building->slug));
        $this->get('/apartments/'.strtoupper($apartment->slug))->assertStatus(301)->assertRedirect(route('apartments.show', $apartment->slug));
        $this->get(route('apartments.show', $apartment->slug))->assertOk();

        $oldSlug = $apartment->slug;
        $apartment->update(['slug' => 'renamed-'.$oldSlug]);
        $this->get('/apartments/'.$oldSlug)->assertStatus(301)->assertRedirect(route('apartments.show', $apartment->slug));
    }

    public function test_inactive_listing_with_a_case_only_old_slug_is_not_found_instead_of_looping(): void
    {
        $building = $this->createBuilding($this->createCity()->id);
        $apartment = $this->createApartment($building->id);
        $apartment->update(['is_active' => false]);
        SlugRedirect::create(['redirectable_type' => Apartment::class, 'redirectable_id' => $apartment->id, 'old_slug' => strtoupper($apartment->slug)]);

        $this->get('/apartments/'.$apartment->slug)->assertNotFound();
        $this->get('/apartments/'.strtoupper($apartment->slug))->assertNotFound();
    }

    public function test_blog_slugs_are_normalised_and_renames_redirect(): void
    {
        $blog = $this->createBlog('Why Choose Places & Studios '.uniqid());

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $blog->slug);
        $this->assertStringNotContainsString('&', $blog->slug);

        $this->get('/blog/'.strtoupper($blog->slug))->assertStatus(301)->assertRedirect(route('blog', $blog->slug));

        $oldSlug = $blog->slug;
        $blog->update(['slug' => 'renamed-'.$oldSlug]);
        $this->get('/blog/'.$oldSlug)->assertStatus(301)->assertRedirect(route('blog', $blog->slug));
        $this->get('/blog/no-such-post-'.uniqid())->assertNotFound();
    }

    private function createCity(): City
    {
        $city = City::create(['name_ar' => 'مدينة', 'name_en' => 'Clean City '.uniqid(), 'slug' => 'clean-city-'.uniqid(), 'sort_order' => 99]);
        $this->createdCityIds[] = $city->id;

        return $city->fresh();
    }

    private function createBuilding(int $cityId, ?string $slug = null): Building
    {
        $building = Building::create([
            'name_ar' => 'مبنى', 'name_en' => 'Clean Building', 'slug' => $slug ?? 'clean-building-'.uniqid(),
            'address' => 'Test Street', 'city_id' => $cityId, 'is_active' => true, 'ttlock_password' => 'test',
        ]);
        $this->createdBuildingIds[] = $building->id;

        return $building->fresh();
    }

    private function createApartment(int $buildingId, ?string $slug = null): Apartment
    {
        $apartment = Apartment::create([
            'name_ar' => 'شقة', 'name_en' => 'Clean Apartment', 'slug' => $slug ?? 'clean-apartment-'.uniqid(),
            'building_id' => $buildingId, 'num_rooms' => 2, 'num_beds' => 2, 'area' => 80, 'price' => 200, 'is_active' => true,
        ]);
        $this->createdApartmentIds[] = $apartment->id;

        return $apartment->fresh();
    }

    private function createBlog(string $slug): Blog
    {
        $blog = Blog::create([
            'name_ar' => 'مقال', 'name_en' => 'Clean Post', 'slug' => $slug,
            'info_ar' => 'نبذة', 'info_en' => 'Intro', 'content_ar' => 'محتوى', 'content_en' => 'Body',
            'seo_title_ar' => 'مقال', 'seo_title_en' => 'Clean Post', 'seo_description_ar' => 'نبذة', 'seo_description_en' => 'Intro',
        ]);
        $this->createdBlogIds[] = $blog->id;

        return $blog->fresh();
    }
}
