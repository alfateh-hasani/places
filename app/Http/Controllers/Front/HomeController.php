<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use App\Models\City;
use App\Models\ContactUs;
use App\Models\Page;
use App\Models\Review;
use App\Models\SiteFeature;
use App\Models\Slider;
use App\Models\SlugRedirect;
use App\Rules\Recaptcha;
use App\Services\HomeApartmentOrderingService;
use App\Services\Pricing\PricingService;
use App\Services\Seo\SeoService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomeApartmentOrderingService $homeApartmentOrderingService,
        private readonly PricingService $pricing,
        private readonly SeoService $seo,
    ) {}

    public function index(Request $request): View|JsonResponse
    {

        $data = [];
        $data['sliders'] = Slider::orderBy('sort_order', 'asc')->get();
        $data['apartments'] = $this->homeApartmentOrderingService->paginateDiversified();

        $checkIn = Carbon::today();
        $checkOut = Carbon::tomorrow();
        $data['apartments']->getCollection()->transform(function (Apartment $apt) use ($checkIn, $checkOut) {
            $apt->priceInfo = $this->pricing->calculate($apt, $checkIn, $checkOut);

            return $apt;
        });

        $data['cities'] = City::orderBy('sort_order', 'asc')->withCount('apartments')->get();
        $data['buildings'] = City::with(['buildings' => fn ($q) => $q->active()])
            ->orderBy('sort_order', 'asc')
            ->whereHas('buildings', fn ($q) => $q->active())
            ->get();

        // جلب المباني مع الإحداثيات للخريطة — المباني غير المفعّلة تُستبعد من الخريطة
        $data['mapBuildings'] = Building::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['city', 'media', 'apartments' => fn ($q) => $q->where('is_active', true)])
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
        $data['features_1'] = SiteFeature::orderBy('sort', 'asc')->limit(3)->get();
        $data['features_2'] = SiteFeature::orderBy('sort', 'asc')->skip(3)->limit(3)->get();

        $this->seo
            ->page(null, null, route('home'))
            ->title($this->seo->siteName().' | '.__('site.seo_title'))
            ->organization();

        return view('home.index', $data);
    }

    public function contactUs(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
            'g-recaptcha-response' => [new Recaptcha('contact_us')],
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

    public function blog(Request $request, $slug): View|RedirectResponse
    {
        $slug = urldecode($slug);
        $blog = Blog::whereSlug($slug)->first();
        if (! $blog) {
            return $this->redirectToRenamedSlug(Blog::class, $slug, 'blog');
        }
        $url = route('blog', $blog->slug);

        if ($slug !== $blog->slug) {
            return redirect()->to($url, 301);
        }
        $blogPage = Page::whereTemplate('blog')->first();

        $this->seo
            ->page($blog->ml('seo_title') ?: $blog->ml('name'), $blog->ml('seo_description') ?: $blog->ml('info'), $url, $blog->image, 'article')
            ->breadcrumbs(array_filter([
                $blogPage ? ['name' => $blogPage->ml('name'), 'url' => route('page', $blogPage->slug)] : null,
                ['name' => $blog->ml('name'), 'url' => $url],
            ]))
            ->blogPost($blog, $url);

        $this->data['blog'] = $blog;
        $this->data['blogs'] = Blog::where('id', '!=', $blog->id)->orderBy('id', 'desc')->take(3)->get();
        $this->data['page'] = $blogPage;

        return view('pages.single_blog', $this->data);
    }

    // apartments-by-city
    public function getApartmentsByCity(Request $request, $slug): View|RedirectResponse
    {
        $slug = urldecode($slug);
        $city = City::whereSlug($slug)->first();
        if (! $city) {
            return $this->redirectToRenamedSlug(City::class, $slug, 'by-city');
        }
        $url = route('by-city', $city->slug);

        // MySQL matches slugs case-insensitively; only the canonical spelling may answer 200.
        if ($slug !== $city->slug) {
            return redirect()->to($url, 301);
        }

        $this->seo
            ->page($city->ml('seo_title') ?: $city->ml('name'), $city->ml('seo_description'), $url, $city->image ?: null)
            ->breadcrumbs([['name' => $city->ml('name'), 'url' => $url]]);

        $this->data['city'] = $city;
        $this->data['apartments'] = $city->apartments()->bookable()->orderBy('id', 'desc')->paginate(30);

        $checkIn = Carbon::today();
        $checkOut = Carbon::tomorrow();
        $this->data['apartments']->getCollection()->transform(function (Apartment $apt) use ($checkIn, $checkOut) {
            $apt->priceInfo = $this->pricing->calculate($apt, $checkIn, $checkOut);

            return $apt;
        });
        $this->data['cities'] = City::orderBy('sort_order', 'asc')->withCount('apartments')->get();
        $this->data['buildings'] = City::with('buildings')->orderBy('sort_order', 'asc')->whereHas('buildings')->get();

        return view('apartment.by-city', $this->data);
    }

    /**
     * A record whose slug was normalised or renamed keeps its old URL alive via a 301 (recorded by
     * the model observers / slug migrations); anything else is a genuine 404.
     */
    private function redirectToRenamedSlug(string $type, string $slug, string $routeName): RedirectResponse
    {
        $model = SlugRedirect::where('redirectable_type', $type)
            ->where('old_slug', $slug)
            ->first()
            ?->redirectable;

        if (! $model) {
            abort(404);
        }

        return redirect()->route($routeName, $model->slug, 301);
    }
}
