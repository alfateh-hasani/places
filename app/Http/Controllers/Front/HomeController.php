<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Blog, Slider, Apartment, City, ContactUs, Page};
use Artesaos\SEOTools\Facades\SEOTools;
use App\Models\Review;

class HomeController extends Controller
{
    //

    public function index(Request $request){

        $data   = [];
        $data['sliders'] = Slider::orderBy('sort_order','asc')->get();
        $data['apartments'] = Apartment::where('is_active', true) ->with('reviews')  ->orderBy('id', 'desc') ->take(10)->get();
        $data['cities']     = City::orderBy('sort_order','asc')->withCount('apartments')->get();
        $data['buildings'] = City::with('buildings')->orderBy('sort_order','asc')    ->whereHas('buildings')  ->get();

        // Fetch top 40 reviews
        $reviews = Review::where('rating', '>=', 4) // Adjust condition as needed
            ->orderBy('rating', 'desc')
            ->with('customer')
            ->take(40)
            ->get();

      
        // Calculate average rating and total reviews
        $averageRating = Review::where('rating', '>=', 4)->avg('rating');
        $totalUsers = Review::where('rating', '>=', 4)->count();

        $averageRating = number_format($averageRating, decimals: 1);

        // Chunk into two arrays
        $chunks = $reviews->chunk(20);
        $data['topReviews1'] = $chunks->get(0) ?? collect(); // First 20 reviews for slider 1
        $data['topReviews2'] = $chunks->get(1) ?? collect(); // Next 20 reviews for slider 2
        $data['averageRating' ] = $averageRating; 
        $data['totalUsers' ] = $totalUsers;


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


    //apartments-by-city
    public function getApartmentsByCity(Request $request, $slug)
    {
        $slug = urldecode($slug);
        $city = City::whereSlug($slug)->first();
        if (!$city) {
            abort(404);
        }
        $this->generateSeo($city);
        $this->data['city'] = $city;
        $this->data['apartments'] = $city->apartments()->where('is_active', true)->orderBy('id', 'desc')->paginate(30);
        $this->data['cities'] = City::orderBy('sort_order', 'asc')->withCount('apartments')->get();
        $this->data['buildings'] = City::with('buildings')->orderBy('sort_order', 'asc')->whereHas('buildings')->get();
        return view('apartment.by-city', $this->data);
    }
}
