<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Blog, Slider, Apartment, City, ContactUs, Page};
use Artesaos\SEOTools\Facades\SEOTools;
// Import Slider model

class HomeController extends Controller
{
    //

    public function index(Request $request){

        $data   = [];
        $data['sliders'] = Slider::orderBy('sort_order','asc')->get();
        $data['apartments'] = Apartment::where('is_active', true) ->with('reviews')  ->orderBy('id', 'desc') ->take(10)->get();
        $data['cities']     = City::orderBy('sort_order','asc')->withCount('apartments')->get();
        $data['buildings'] = City::with('buildings')->orderBy('sort_order','asc')    ->whereHas('buildings')  ->get();

        // apartments
        return view('home.index',$data);
    }

    public function contactUs(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
            'subject' => 'required',
        ]);
        $data=[
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'message' => $request->message,
            'subject' => $request->subject,
        ];
        ContactUs::create($data);
        return response()->json(['success' => true, 'message' => __('site.contact_us_success')]);

    }

    public function blog(Request $request, $slug)
    {
        $slug = urldecode($slug);
        $blog = Blog::whereSlug($slug)->first();
        if (!$blog) {
            abort(404);
        }
        $this->generateSeo($blog);
        $this->data['blog'] = $blog;
        $this->data['blogs'] = Blog::where('id', '!=', $blog->id)->orderBy('id', 'desc')->take(3)->get();
        $this->data['page'] = Page::whereTemplate('blog')->first();
        return view('pages.single_blog', $this->data);
    }

    private function generateSeo($page)
    {
        SEOTools::setTitle($page->seo_title);
        SEOTools::setDescription($page->seo_description);
        SEOTools::opengraph()->setUrl(route('page',$page->slug));
        SEOTools::setCanonical(route('page',$page->slug));
        SEOTools::opengraph()->addProperty('type', 'articles');

    }
}
