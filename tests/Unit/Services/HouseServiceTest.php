<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\City;
use App\Models\House;
use App\Services\HouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseServiceTest extends TestCase
{
    use RefreshDatabase;

    private HouseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HouseService;
    }

    /**
     * Test searchHouses returns all houses when no filters.
     */
    public function test_search_houses_returns_all_without_filters(): void
    {
        House::factory()->count(5)->create();

        $result = $this->service->searchHouses([]);

        $this->assertCount(5, $result);
    }

    /**
     * Test searchHouses filters by city_id.
     */
    public function test_search_houses_filters_by_city(): void
    {
        $cityJakarta = City::factory()->create(['name' => 'Jakarta']);
        $cityBandung = City::factory()->create(['name' => 'Bandung']);

        House::factory()->count(3)->create(['city_id' => $cityJakarta->id]);
        House::factory()->count(2)->create(['city_id' => $cityBandung->id]);

        $result = $this->service->searchHouses(['city_id' => $cityJakarta->id]);

        $this->assertCount(3, $result);
        $result->each(function ($house) use ($cityJakarta) {
            $this->assertEquals($cityJakarta->id, $house->city_id);
        });
    }

    /**
     * Test searchHouses filters by category_id.
     */
    public function test_search_houses_filters_by_category(): void
    {
        $categoryRumah = Category::factory()->create(['name' => 'Rumah']);
        $categoryApartemen = Category::factory()->create(['name' => 'Apartemen']);

        House::factory()->count(4)->create(['category_id' => $categoryRumah->id]);
        House::factory()->count(1)->create(['category_id' => $categoryApartemen->id]);

        $result = $this->service->searchHouses(['category_id' => $categoryRumah->id]);

        $this->assertCount(4, $result);
    }

    /**
     * Test searchHouses filters by both city and category.
     */
    public function test_search_houses_filters_by_city_and_category(): void
    {
        $cityJakarta = City::factory()->create();
        $cityBandung = City::factory()->create();
        $categoryRumah = Category::factory()->create();
        $categoryApartemen = Category::factory()->create();

        House::factory()->create([
            'city_id' => $cityJakarta->id,
            'category_id' => $categoryRumah->id,
        ]);

        House::factory()->create([
            'city_id' => $cityJakarta->id,
            'category_id' => $categoryApartemen->id,
        ]);

        House::factory()->create([
            'city_id' => $cityBandung->id,
            'category_id' => $categoryRumah->id,
        ]);

        $result = $this->service->searchHouses([
            'city_id' => $cityJakarta->id,
            'category_id' => $categoryRumah->id,
        ]);

        $this->assertCount(1, $result);
    }

    /**
     * Test getHouseDetails eager loads all relationships.
     */
    public function test_get_house_details_eager_loads_relationships(): void
    {
        $house = House::factory()->create();

        $result = $this->service->getHouseDetails($house);

        $this->assertTrue($result->relationLoaded('category'));
        $this->assertTrue($result->relationLoaded('city'));
        $this->assertTrue($result->relationLoaded('photos'));
        $this->assertTrue($result->relationLoaded('facilities'));
    }

    /**
     * Test getCategoriesAndCities returns both lists.
     */
    public function test_get_categories_and_cities_returns_both(): void
    {
        Category::factory()->count(3)->create();
        City::factory()->count(4)->create();

        $result = $this->service->getCategoriesAndCities();

        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('cities', $result);
        $this->assertCount(3, $result['categories']);
        $this->assertCount(4, $result['cities']);
    }
}
