<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Slider,Apartment, Blog, City, FaqCategory, Page}; // Import Slider model
use Artesaos\SEOTools\Facades\SEOTools;
use Config;

class PageController extends Controller
{

    public function index(Request $request, $slug)
    {
        $slug = urldecode($slug);
        $page = Page::whereSlug($slug)->first();

        if (!$page) {
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
        $this->data['email'] =  Config::get('settings.email');
        $this->data['phone'] =  Config::get('settings.phone');
        $this->data['address'] =  Config::get('settings.address_'.app()->getLocale());
        return view('pages.contact', $this->data);
    }


    private function generateSeo($page)
    {
        SEOTools::setTitle($page->seo_title);
        SEOTools::setDescription($page->seo_description);
        SEOTools::opengraph()->setUrl(route('page',$page->slug));
        SEOTools::setCanonical(route('page',$page->slug));
        SEOTools::opengraph()->addProperty('type', 'articles');

    }

    private function aboutPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        return view('pages.about', $this->data);
    }

    private function faqPage(Page $page)
    {
        $this->generateSeo($page);
        $this->data['page'] = $page;
        $this->data['categories'] = FaqCategory::with('questions')->get();
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
