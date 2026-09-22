<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleWishlistRequest;
use App\Models\Apartment;
use App\Services\WishlistService;
use App\Traits\generateSeoTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    use generateSeoTrait;

    public function __construct(private WishlistService $wishlist)
    {
    }

    public function index(): View
    {
        $customer = Auth::guard('customer')->user();
        $favorites = $customer->favoriteApartments()
            ->orderByPivot('created_at', 'desc')
            ->get();

        $this->generateSeo(
            __('customer.favorite').' | '.__('site.seo_title'),
            __('customer.favorite'),
            route('customer.favorite')
        );

        return view('customer.favorite', [
            'favorites' => $favorites,
            'customer' => $customer,
            'total_favorites' => $favorites->count(),
        ]);
    }

    public function toggle(ToggleWishlistRequest $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        $apartment = Apartment::findOrFail($request->integer('apartment_id'));

        $result = $this->wishlist->toggle($customer, $apartment);

        return response()->json([
            'success' => true,
            'action' => $result['action'],
            'favorited' => $result['favorited'],
            'count' => $result['count'],
            'message' => __('apartment.favorite_'.$result['action']),
        ]);
    }
}
