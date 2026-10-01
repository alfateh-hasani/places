<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Building;
use App\Models\City;
use Tests\TestCase;

class ApartmentSearchTest extends TestCase
{
    private array $createdApartmentIds = [];

    private array $createdBuildingIds = [];

    private array $createdCityIds = [];

    protected function tearDown(): void
    {
        Apartment::whereIn('id', $this->createdApartmentIds)->delete();

        if ($this->createdBuildingIds) {
            Building::whereIn('id', $this->createdBuildingIds)->delete();
        }

        if ($this->createdCityIds) {
            City::whereIn('id', $this->createdCityIds)->delete();
        }

        parent::tearDown();
    }

    public function test_search_excludes_inactive_apartments_when_no_filters_are_provided(): void
    {
        $city = $this->createCity();
        $building = $this->createBuilding($city->id);

        $active = $this->createApartment(true, $building->id);
        $inactive = $this->createApartment(false, $building->id);

        $response = $this->get(route('apartments.search'));

        $response->assertOk();
        $response->assertSee('/apartments/'.$active->slug);
        $response->assertDontSee('/apartments/'.$inactive->slug);
    }

    public function test_search_excludes_inactive_apartments_when_filtering_by_city(): void
    {
        $city = $this->createCity();
        $building = $this->createBuilding($city->id);

        $active = $this->createApartment(true, $building->id);
        $inactive = $this->createApartment(false, $building->id);

        $response = $this->get(route('apartments.search', ['city_id' => $city->id]));

        $response->assertOk();
        $response->assertSee('/apartments/'.$active->slug);
        $response->assertDontSee('/apartments/'.$inactive->slug);
    }

    private function createCity(): City
    {
        $city = City::create([
            'name_ar' => 'مدينة',
            'name_en' => 'City',
            'slug' => 'city-'.uniqid(),
            'sort_order' => 1,
        ]);
        $this->createdCityIds[] = $city->id;

        return $city;
    }

    private function createBuilding(int $cityId): Building
    {
        $building = Building::create([
            'name_ar' => 'مبنى',
            'name_en' => 'Building',
            'address' => 'Test Street',
            'city_id' => $cityId,
            'ttlock_password' => 'test-password',
        ]);
        $this->createdBuildingIds[] = $building->id;

        return $building;
    }

    private function createApartment(bool $isActive, ?int $buildingId = null): Apartment
    {
        $suffix = ($isActive ? 'Active' : 'Inactive').'-'.uniqid();

        $apartment = Apartment::create([
            'name_ar' => 'شقة اختبار',
            'name_en' => 'Test Apartment '.$suffix,
            'slug' => 'test-apartment-'.strtolower($suffix),
            'building_id' => $buildingId,
            'num_rooms' => 2,
            'num_beds' => 2,
            'area' => 80,
            'price' => 200.00,
            'is_active' => $isActive,
        ]);

        $this->createdApartmentIds[] = $apartment->id;

        return $apartment;
    }
}
