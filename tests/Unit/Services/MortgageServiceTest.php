<?php

namespace Tests\Unit\Services;

use App\Models\House;
use App\Models\Interest;
use App\Models\MortgageRequest;
use App\Models\User;
use App\Services\MortgageService;
use Database\Seeders\RoleAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MortgageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MortgageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MortgageService;
        $this->seed(RoleAdminSeeder::class);
    }

    /**
     * Test calculateMortgageDetails with standard parameters.
     */
    public function test_calculate_mortgage_details_with_standard_parameters(): void
    {
        $housePrice = 1000000000;
        $dpPercentage = 20;
        $interestRate = 5;
        $duration = 15;

        $result = $this->service->calculateMortgageDetails(
            housePrice: $housePrice,
            dpPercentage: $dpPercentage,
            interestRate: $interestRate,
            duration: $duration
        );

        $this->assertArrayHasKey('dp_total_amount', $result);
        $this->assertArrayHasKey('loan_total_amount', $result);
        $this->assertArrayHasKey('monthly_amount', $result);
        $this->assertArrayHasKey('loan_interest_total_amount', $result);

        $this->assertEquals(200000000, $result['dp_total_amount']);
        $this->assertEquals(800000000, $result['loan_total_amount']);
        $this->assertGreaterThan(0, $result['monthly_amount']);

        $expectedMonthly = $this->calculateExpectedMonthly(800000000, 5, 15);
        $this->assertEqualsWithDelta($expectedMonthly, $result['monthly_amount'], 1);
    }

    /**
     * Test calculateMortgageDetails with minimum down payment (10%).
     */
    public function test_calculate_mortgage_with_minimum_down_payment(): void
    {
        $result = $this->service->calculateMortgageDetails(
            housePrice: 500000000,
            dpPercentage: 10,
            interestRate: 8,
            duration: 10
        );

        $this->assertEquals(50000000, $result['dp_total_amount']);
        $this->assertEquals(450000000, $result['loan_total_amount']);
    }

    /**
     * Test calculateMortgageDetails with maximum down payment (80%).
     */
    public function test_calculate_mortgage_with_maximum_down_payment(): void
    {
        $result = $this->service->calculateMortgageDetails(
            housePrice: 1000000000,
            dpPercentage: 80,
            interestRate: 5,
            duration: 5
        );

        $this->assertEquals(800000000, $result['dp_total_amount']);
        $this->assertEquals(200000000, $result['loan_total_amount']);
    }

    /**
     * Test handleInterestRequest creates mortgage request successfully.
     */
    public function test_handle_interest_request_creates_mortgage_successfully(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $house = House::factory()->create(['price' => 1000000000]);
        $interest = Interest::factory()->create([
            'house_id' => $house->id,
            'interest' => 5,
            'duration' => 15,
        ]);

        $request = new \Illuminate\Http\Request([
            'house_id' => $house->id,
            'interest_id' => $interest->id,
            'dp_percentage' => 20,
            'duration' => 15,
            'bank_name' => 'BCA',
            'interest' => 5,
        ]);

        Storage::fake('public');

        $mortgageRequest = $this->service->handleInterestRequest($request);

        $this->assertInstanceOf(MortgageRequest::class, $mortgageRequest);
        $this->assertDatabaseHas('mortgage_requests', [
            'user_id' => $user->id,
            'house_id' => $house->id,
            'status' => 'Waiting for Bank',
            'dp_percentage' => 20,
        ]);

        $this->assertEquals(200000000, $mortgageRequest->dp_total_amount);
        $this->assertEquals(800000000, $mortgageRequest->loan_total_amount);
    }

    /**
     * Test handleInterestRequest with document upload.
     */
    public function test_handle_interest_request_uploads_documents(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->assignRole('customer');
        $house = House::factory()->create();
        $interest = Interest::factory()->create(['house_id' => $house->id]);

        $request = new \Illuminate\Http\Request([
            'house_id' => $house->id,
            'interest_id' => $interest->id,
            'dp_percentage' => 20,
            'duration' => 15,
            'bank_name' => 'BCA',
            'interest' => 5,
        ]);

        $request->files->set('documents', UploadedFile::fake()->create('document.pdf', 100));

        $mortgageRequest = $this->service->handleInterestRequest($request);

        Storage::disk('public')->assertExists($mortgageRequest->documents);
    }

    /**
     * Test handleInterestRequest with validation errors.
     */
    public function test_handle_interest_request_throws_exception_for_invalid_house(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $request = new \Illuminate\Http\Request([
            'house_id' => 99999,
            'interest_id' => 1,
            'dp_percentage' => 20,
        ]);

        $this->service->handleInterestRequest($request);
    }

    /**
     * Test getUserMortgages returns only user's mortgages.
     */
    public function test_get_user_mortgages_returns_only_users_data(): void
    {
        $user1 = User::factory()->create();
        $user1->assignRole('customer');
        $user2 = User::factory()->create();
        $user2->assignRole('customer');

        $house = House::factory()->create();

        MortgageRequest::factory()->create([
            'user_id' => $user1->id,
            'house_id' => $house->id,
        ]);
        MortgageRequest::factory()->create([
            'user_id' => $user2->id,
            'house_id' => $house->id,
        ]);

        $result = $this->service->getUserMortgages($user1->id);

        $this->assertCount(1, $result);
        $this->assertEquals($user1->id, $result->first()->user_id);
    }

    /**
     * Test getMortgageDetails eager loads relationships.
     */
    public function test_get_mortgage_details_eager_loads_installments(): void
    {
        $user = User::factory()->create();
        $house = House::factory()->create();
        $mortgage = MortgageRequest::factory()->create([
            'user_id' => $user->id,
            'house_id' => $house->id,
        ]);

        $result = $this->service->getMortgageDetails($mortgage);

        $this->assertTrue($result->relationLoaded('installments'));
        $this->assertTrue($result->relationLoaded('house'));
        $this->assertTrue($result->relationLoaded('interest'));
    }

    /**
     * Helper method: Calculate expected monthly payment using amortization formula.
     */
    private function calculateExpectedMonthly(
        float $principal,
        float $annualRate,
        int $years
    ): float {
        $monthlyRate = ($annualRate / 100) / 12;
        $numPayments = $years * 12;

        if ($monthlyRate == 0) {
            return $principal / $numPayments;
        }

        return $principal * (
            ($monthlyRate * pow(1 + $monthlyRate, $numPayments)) /
            (pow(1 + $monthlyRate, $numPayments) - 1)
        );
    }
}
