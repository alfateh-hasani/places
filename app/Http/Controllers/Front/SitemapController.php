<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use App\Models\City;
use App\Models\Page;
use Illuminate\Http\Response;
use LaravelLocalization;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $locales = array_keys(LaravelLocalization::getSupportedLocales());

        $entries = collect([
            $this->entry(route('home'), now(), 'daily', '1.0'),
        ]);

        Apartment::bookable()->select('slug', 'updated_at')->get()
            ->each(fn (Apartment $apartment) => $entries->push(
                $this->entry(route('apartments.show', $apartment->slug), $apartment->updated_at, 'weekly', '0.8')
            ));

        Building::active()->select('slug', 'updated_at')->get()
            ->each(fn (Building $building) => $entries->push(
                $this->entry(route('building.details', $building->slug), $building->updated_at, 'weekly', '0.7')
            ));

        City::select('slug', 'updated_at')->get()
            ->each(fn (City $city) => $entries->push(
                $this->entry(route('by-city', $city->slug), $city->updated_at, 'weekly', '0.6')
            ));

        Blog::select('slug', 'updated_at')->get()
            ->each(fn (Blog $blog) => $entries->push(
                $this->entry(route('blog', $blog->slug), $blog->updated_at, 'monthly', '0.5')
            ));

        Page::where('template', '!=', 'about')->select('slug', 'updated_at')->get()
            ->each(fn (Page $page) => $entries->push(
                $this->entry(route('page', $page->slug), $page->updated_at, 'monthly', '0.5')
            ));

        $xml = '<'.'?xml version="1.0" encoding="UTF-8"?'.">\n".view('sitemap.index', [
            'entries' => $entries->filter()->values(),
            'locales' => $locales,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @return array{loc: string, lastmod: string, changefreq: string, priority: string}|null
     */
    private function entry(?string $url, $lastmod, string $changefreq, string $priority): ?array
    {
        if (! $url) {
            return null;
        }

        return [
            'loc' => $url,
            'lastmod' => $lastmod ? $lastmod->toAtomString() : now()->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
