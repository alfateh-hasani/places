<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Customer;

/**
 * Encapsulates all wishlist (favorites) business rules so controllers stay thin
 * and the persistence details live in one place.
 */
class WishlistService
{
    /**
     * Toggle an apartment in the customer's wishlist.
     *
     * @return array{action: string, favorited: bool, count: int}
     */
    public function toggle(Customer $customer, Apartment $apartment): array
    {
        $result = $customer->favoriteApartments()->toggle($apartment->id);
        $favorited = count($result['attached']) > 0;

        return [
            'action' => $favorited ? 'added' : 'removed',
            'favorited' => $favorited,
            'count' => $this->count($customer),
        ];
    }

    public function count(Customer $customer): int
    {
        return $customer->favoriteApartments()->count();
    }

    public function has(Customer $customer, Apartment $apartment): bool
    {
        return $customer->favoriteApartments()->whereKey($apartment->id)->exists();
    }
}
