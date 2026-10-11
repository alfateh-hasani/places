<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\FaqCategory;
use App\Models\Page;
use App\Services\Seo\SeoService;
use Config;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function __construct(private readonly SeoService $seo) {}

    public function index(Request $request, $slug)
    {
        $slug = urldecode($slug);
        $page = Page::whereSlug($slug)->first();

        if (! $page) {
            abort(404);
        }
        $template = $page->template;
        switch ($template) {
            case 'contact':
                return $this->contactPage($page);
            case 'about':
                return $this->aboutPage($page);
            case 'faq':
                return $this->faqPage($page);
            case 'privacy':
                return $this->privacyPage($page);
            case 'terms':
                return $this->termsPage($page);
            case 'blog':
                return $this->blogPage($page);
            default:
        }
    }

    private function contactPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        $this->data['email'] = Config::get('settings.email');
        $this->data['phone'] = Config::get('settings.phone');
        $this->data['map'] = Config::get('settings.map');
        $this->data['address'] = Config::get('settings.address_'.app()->getLocale());

        return view('pages.contact', $this->data);
    }

    private function generateSeo(Page $page): void
    {
        $url = route('page', $page->slug);

        $this->seo
            ->page($page->ml('seo_title') ?: $page->ml('name'), $page->ml('seo_description'), $url, $page->image ?: null)
            ->breadcrumbs([['name' => $page->ml('name'), 'url' => $url]]);
    }

    private function aboutPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        // return view('pages.about', $this->data);
    }

    private function faqPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        $this->data['categories'] = FaqCategory::with('questions')->get();
        $this->seo->faq($this->data['categories']);

        return view('pages.faq', $this->data);
    }

    private function privacyPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;

        return view('pages.privacy', $this->data);
    }

    private function termsPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;

        return view('pages.terms', $this->data);
    }

    private function blogPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        $this->data['blogs'] = Blog::with('media')->latest()->paginate(30);

        return view('pages.blog', $this->data);
    }
}
