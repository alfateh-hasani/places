<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\City;

class CityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = City::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name_ar' => $this->faker->regexify('[A-Za-z0-9]{191}'),
            'name_en' => $this->faker->regexify('[A-Za-z0-9]{191}'),
            'sort_order' => $this->faker->numberBetween(-100000, 100000),
        ];
    }
}
