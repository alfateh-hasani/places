<?php

namespace App\Services\Seo;

use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use App\Models\City;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Review;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use LaravelLocalization;

/**
 * Fills the <head> SEO tags (title, description, canonical, OpenGraph, Twitter) and the
 * schema.org JSON-LD for every public page, so controllers never touch SEOTools directly.
 *
 * JSON-LD must go through jsonLdMulti(): SEOTools::generate() only renders that collection,
 * anything written to the single jsonLd() instance is silently dropped.
 */
class SeoService
{
    /** @var array<string, string> */
    private const OG_LOCALES = ['ar' => 'ar_SA', 'en' => 'en_GB'];

    private const MAX_DESCRIPTION_LENGTH = 160;

    public function siteName(): string
    {
        return Config::get('settings.seo_title_'.app()->getLocale()) ?: Config::get('app.name');
    }

    public function siteDescription(): string
    {
        return Config::get('settings.seo_description_'.app()->getLocale())
            ?: Config::get('seotools.meta.defaults.description', '');
    }

    public function defaultImage(): string
    {
        return asset('img/og-default.png');
    }

    public function fullTitle(?string $pageTitle): string
    {
        $pageTitle = trim((string) $pageTitle);

        return $pageTitle === '' ? $this->siteName() : $pageTitle.' | '.$this->siteName();
    }

    public function ogLocale(string $locale): string
    {
        return self::OG_LOCALES[$locale] ?? $locale;
    }

    /**
     * Standard page tags. $title is the page part only, the site name is appended.
     */
    public function page(?string $title, ?string $description, string $url, ?string $image = null, string $ogType = 'website'): self
    {
        $description = $this->cleanDescription($description ?: $this->siteDescription());
        $image = $image ?: $this->defaultImage();
        $locale = app()->getLocale();

        SEOTools::setTitle($this->fullTitle($title));
        SEOTools::setDescription($description);
        SEOTools::setCanonical($url);

        $openGraph = SEOTools::opengraph();
        $openGraph->setUrl($url)
            ->setSiteName($this->siteName())
            ->addProperty('type', $ogType)
            ->addProperty('locale', $this->ogLocale($locale))
            ->addImage($image);

        foreach (array_keys(self::OG_LOCALES) as $alternate) {
            if ($alternate !== $locale) {
                $openGraph->addProperty('locale:alternate', $this->ogLocale($alternate));
            }
        }

        SEOTools::twitter()->setImage($image);

        return $this;
    }

    /**
     * Overrides the "<page> | <site>" pattern with an exact title (the home page puts the brand first).
     */
    public function title(string $fullTitle): self
    {
        SEOTools::setTitle($fullTitle);

        return $this;
    }

    /**
     * Private or duplicate pages (search results, customer area) stay out of the index.
     */
    public function noIndex(): self
    {
        SEOTools::metatags()->setRobots('noindex, follow');

        return $this;
    }

    /**
     * BreadcrumbList so search results show the site hierarchy instead of a raw URL.
     *
     * @param  array<int, array{name: string, url: string}>  $crumbs  Without the home crumb, which is added here.
     */
    public function breadcrumbs(array $crumbs): self
    {
        $items = array_merge([['name' => __('site.home'), 'url' => route('home')]], $crumbs);

        $this->addJsonLd('BreadcrumbList', [
            'itemListElement' => collect($items)->values()->map(fn (array $crumb, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ])->all(),
        ]);

        return $this;
    }

    /**
     * Organization + WebSite graph for the home page (knowledge panel, site name and logo in results).
     */
    public function organization(): self
    {
        $sameAs = collect(['facebook', 'instagram', 'x', 'linkedin', 'youtube', 'tiktok', 'snapchat'])
            ->map(fn (string $key) => Config::get('settings.'.$key))
            ->filter(fn ($value) => is_string($value) && Str::startsWith($value, 'http'))
            ->values()
            ->all();

        $siteRoot = $this->siteRoot();

        $organization = array_filter([
            '@id' => $siteRoot.'/#organization',
            'name' => $this->siteName(),
            'url' => $siteRoot,
            'logo' => asset('img/og-default.png'),
            'sameAs' => $sameAs ?: null,
            'contactPoint' => $this->contactPoint(),
            'address' => $this->postalAddress(),
        ]);

        $this->addJsonLd('Organization', $organization);

        $this->addJsonLd('WebSite', [
            '@id' => $siteRoot.'/#website',
            'name' => $this->siteName(),
            'url' => $siteRoot,
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => $siteRoot.'/#organization'],
        ]);

        return $this;
    }

    /**
     * Product + Offer markup makes a unit eligible for price/availability snippets.
     *
     * @param  array<string, mixed>  $priceInfo
     */
    public function apartment(Apartment $apartment, string $url, array $priceInfo): self
    {
        $reviews = $apartment->relationLoaded('reviews') ? $apartment->reviews : collect();

        $data = array_filter([
            'name' => $apartment->ml('name'),
            'description' => $this->cleanDescription($apartment->ml('description'), 500),
            'url' => $url,
            'image' => $apartment->image_view ?: $this->defaultImage(),
            'sku' => (string) $apartment->id,
            'brand' => ['@type' => 'Brand', 'name' => $this->siteName()],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'SAR',
                'price' => (string) ($priceInfo['one_night_price'] ?? $apartment->price),
                'availability' => 'https://schema.org/InStock',
                'url' => $url,
            ],
            'aggregateRating' => $this->aggregateRating($reviews),
            'address' => $apartment->building ? $this->buildingAddress($apartment->building) : null,
        ]);

        $this->addJsonLd('Product', $data);

        return $this;
    }

    public function building(Building $building, string $url, int $unitCount): self
    {
        $data = array_filter([
            'name' => $building->ml('name'),
            'description' => $this->cleanDescription($building->ml('address'), 500),
            'url' => $url,
            'image' => $building->image ?: $this->defaultImage(),
            'numberOfAccommodationUnits' => $unitCount,
            'address' => $this->buildingAddress($building),
            'geo' => ($building->latitude && $building->longitude) ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $building->latitude,
                'longitude' => (float) $building->longitude,
            ] : null,
        ]);

        $this->addJsonLd('ApartmentComplex', $data);

        return $this;
    }

    public function blogPost(Blog $blog, string $url): self
    {
        $this->addJsonLd('BlogPosting', array_filter([
            'headline' => $blog->ml('name'),
            'description' => $this->cleanDescription($blog->ml('info') ?: $blog->ml('seo_description')),
            'image' => $blog->image ?: $this->defaultImage(),
            'url' => $url,
            'mainEntityOfPage' => $url,
            'datePublished' => $blog->created_at?->toAtomString(),
            'dateModified' => $blog->updated_at?->toAtomString(),
            'inLanguage' => app()->getLocale(),
            'author' => ['@type' => 'Organization', 'name' => $this->siteName(), 'url' => route('home')],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName(),
                'logo' => ['@type' => 'ImageObject', 'url' => asset('img/og-default.png')],
            ],
        ]));

        return $this;
    }

    /**
     * @param  Collection<int, FaqCategory>  $categories  With their questions loaded.
     */
    public function faq(Collection $categories): self
    {
        $questions = $categories
            ->flatMap(fn (FaqCategory $category) => $category->questions)
            ->filter(fn (Faq $faq) => trim((string) $faq->ml('title')) !== '' && trim(strip_tags((string) $faq->ml('description'))) !== '')
            ->map(fn (Faq $faq): array => [
                '@type' => 'Question',
                'name' => trim((string) $faq->ml('title')),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => trim(strip_tags((string) $faq->ml('description'))),
                ],
            ])
            ->values()
            ->all();

        if ($questions !== []) {
            $this->addJsonLd('FAQPage', ['mainEntity' => $questions]);
        }

        return $this;
    }

    public function cityName(City $city): string
    {
        return (string) $city->ml('name');
    }

    /**
     * Strips markup and trims to a search-snippet friendly length.
     */
    public function cleanDescription(?string $text, int $limit = self::MAX_DESCRIPTION_LENGTH): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $text))) ?? '');

        return Str::limit($text, $limit, '…');
    }

    /**
     * The locale-independent site origin, so Organization/WebSite ids are identical on /ar and /en pages.
     */
    private function siteRoot(): string
    {
        return rtrim(LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale(), route('home')), '/');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addJsonLd(string $type, array $data): void
    {
        $jsonLd = SEOTools::jsonLdMulti();

        if (! $jsonLd->isEmpty()) {
            $jsonLd->newJsonLd();
        }

        // A new block inherits the config defaults (site name/description); only the WebPage block wants those.
        $jsonLd->setTitle(null)->setDescription(null)->setType($type)->addValues($data);

        // Keep the default WebPage block selected so later setTitle()/setDescription() calls land there.
        $jsonLd->select(0);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildingAddress(Building $building): ?array
    {
        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $building->ml('address'),
            'addressLocality' => $building->city?->ml('name'),
            'addressCountry' => 'SA',
        ]);

        return count($address) > 2 ? $address : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function postalAddress(): ?array
    {
        $address = Config::get('settings.address_'.app()->getLocale());

        if (! is_string($address) || trim($address) === '') {
            return null;
        }

        return [
            '@type' => 'PostalAddress',
            'streetAddress' => trim($address),
            'addressCountry' => 'SA',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function contactPoint(): ?array
    {
        $phone = Config::get('settings.phone');

        if (! is_string($phone) || trim($phone) === '') {
            return null;
        }

        return array_filter([
            '@type' => 'ContactPoint',
            'telephone' => trim($phone),
            'contactType' => 'customer service',
            'email' => Config::get('settings.email') ?: null,
            'availableLanguage' => ['ar', 'en'],
        ]);
    }

    /**
     * @param  Collection<int, Review>  $reviews
     * @return array<string, mixed>|null
     */
    private function aggregateRating(Collection $reviews): ?array
    {
        $rated = $reviews->filter(fn ($review) => is_numeric($review->rating) && $review->rating > 0);

        if ($rated->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $rated->avg('rating'), 1),
            'reviewCount' => $rated->count(),
            'bestRating' => 5,
            'worstRating' => 1,
        ];
    }
}
