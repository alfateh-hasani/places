<?php

namespace Tests\Unit;

use App\Services\HomeApartmentOrderingService;
use PHPUnit\Framework\TestCase;

class HomeApartmentOrderingServiceTest extends TestCase
{
    public function test_it_interleaves_apartments_across_buildings(): void
    {
        $service = new HomeApartmentOrderingService;

        $apartmentIds = $service->interleaveApartmentIdsByBuilding(collect([
            ['id' => 30, 'building_id' => 1],
            ['id' => 29, 'building_id' => 1],
            ['id' => 28, 'building_id' => 2],
            ['id' => 27, 'building_id' => 2],
            ['id' => 26, 'building_id' => 3],
        ]));

        $this->assertSame([30, 28, 26, 29, 27], $apartmentIds->all());
    }

    public function test_it_preserves_the_original_building_priority_when_rotating(): void
    {
        $service = new HomeApartmentOrderingService;

        $apartmentIds = $service->interleaveApartmentIdsByBuilding(collect([
            ['id' => 50, 'building_id' => 9],
            ['id' => 48, 'building_id' => 9],
            ['id' => 47, 'building_id' => 7],
            ['id' => 46, 'building_id' => 7],
            ['id' => 45, 'building_id' => 7],
            ['id' => 44, 'building_id' => 3],
        ]));

        $this->assertSame([50, 47, 44, 48, 46, 45], $apartmentIds->all());
    }
}
