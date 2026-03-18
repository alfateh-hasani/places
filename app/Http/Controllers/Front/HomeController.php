<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Blog;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\Page;
use App\Models\Review;
use App\Models\SiteFeature;
use App\Models\Slider;
use App\Services\HomeApartmentOrderingService;
use Artesaos\SEOTools\Facades\SEOTools;
use Config;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly HomeApartmentOrderingService $homeApartmentOrderingService) {}

    public function index(Request $request): View|JsonResponse
    {

        $data = [];
        $data['sliders'] = Slider::orderBy('sort_order', 'asc')->get();
        $data['apartments'] = $this->homeApartmentOrderingService->paginateDiversified();

        $data['cities'] = City::orderBy('sort_order', 'asc')->withCount('apartments')->get();
        $data['buildings'] = City::with('buildings')->orderBy('sort_order', 'asc')->whereHas('buildings')->get();

        // جلب المباني مع الإحداثيات للخريطة
        $data['mapBuildings'] = \App\Models\Building::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['city', 'media', 'apartments'])
            ->get()
            ->map(function ($building) {
                $building->apartments_count = $building->apartments->count();
                // التأكد من وجود رابط الصورة
                $building->image = $building->getFirstMediaUrl('image');

                return $building;
            });

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
        $data['averageRating'] = $averageRating;
        $data['totalUsers'] = $totalUsers;
        if ($request->ajax()) {
            return response()->json([
                'html' => $data['apartments']->getCollection()
                    ->map(fn (Apartment $apartment): string => view('apartment.card', ['apartment' => $apartment])->render())
                    ->implode(''),
                'nextPage' => $data['apartments']->hasMorePages() ? $data['apartments']->currentPage() + 1 : null,
            ]);
        }
        $seo_title = Config::get('settings.seo_title_'.app()->getLocale()).' | '.__('site.seo_title');
        $seo_description = Config::get('settings.seo_description_'.app()->getLocale());
        $data['features_1'] = SiteFeature::orderBy('sort', 'asc')->limit(3)->get();
        $data['features_2'] = SiteFeature::orderBy('sort', 'asc')->skip(3)->limit(3)->get();
        SEOTools::setTitle($seo_title);
        SEOTools::setDescription($seo_description);
        SEOTools::opengraph()->setUrl(route('home'));
        SEOTools::setCanonical(route('home'));
        SEOTools::opengraph()->addProperty('type', 'website');

        // apartments
        return view('home.index', $data);
    }

    public function contactUs(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
        ]);
        $data = [
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
        if (! $blog) {
            abort(404);
        }
        $seo_title = $blog->ml('seo_title').' | '.Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = $blog->ml('seo_description');
        $url = route('blog', $blog->slug);
        $this->generateSeo($seo_title, $seo_description, $url);

        $this->data['blog'] = $blog;
        $this->data['blogs'] = Blog::where('id', '!=', $blog->id)->orderBy('id', 'desc')->take(3)->get();
        $this->data['page'] = Page::whereTemplate('blog')->first();

        return view('pages.single_blog', $this->data);
    }

    private function generateSeo($seo_title, $seo_description, $url)
    {
        SEOTools::setTitle($seo_title);
        SEOTools::setDescription($seo_description);
        SEOTools::opengraph()->setUrl($url);
        SEOTools::setCanonical($url);
        SEOTools::opengraph()->addProperty('type', 'articles');

    }

    // apartments-by-city
    public function getApartmentsByCity(Request $request, $slug)
    {
        $slug = urldecode($slug);
        $city = City::whereSlug($slug)->first();
        if (! $city) {
            abort(404);
        }
        $seo_top_title = $city->ml('seo_title').' | '.Config::get('settings.seo_title_'.app()->getLocale());
        $seo_description = $city->ml('seo_description');
        $url = route('by-city', $city->slug);
        $this->generateSeo($seo_top_title, $seo_description, $url);
        $this->data['city'] = $city;
        $this->data['apartments'] = $city->apartments()->where('is_active', true)->orderBy('id', 'desc')->paginate(30);
        $this->data['cities'] = City::orderBy('sort_order', 'asc')->withCount('apartments')->get();
        $this->data['buildings'] = City::with('buildings')->orderBy('sort_order', 'asc')->whereHas('buildings')->get();

        return view('apartment.by-city', $this->data);
    }
}
