<?php

namespace Tests\Unit\Models;

use App\Models\House;
use App\Models\Installment;
use App\Models\MortgageRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MortgageRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test mortgage request belongs to user.
     */
    public function test_mortgage_request_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $mortgage = MortgageRequest::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $mortgage->user);
        $this->assertEquals($user->id, $mortgage->user->id);
    }

    /**
     * Test mortgage request belongs to house.
     */
    public function test_mortgage_request_belongs_to_house(): void
    {
        $house = House::factory()->create();
        $mortgage = MortgageRequest::factory()->create(['house_id' => $house->id]);

        $this->assertInstanceOf(House::class, $mortgage->house);
        $this->assertEquals($house->id, $mortgage->house->id);
    }

    /**
     * Test mortgage request has many installments.
     */
    public function test_mortgage_request_has_many_installments(): void
    {
        $mortgage = MortgageRequest::factory()->create();
        Installment::factory()->count(3)->create([
            'mortgage_request_id' => $mortgage->id,
        ]);

        $this->assertCount(3, $mortgage->installments);
        $this->assertInstanceOf(Installment::class, $mortgage->installments->first());
    }

    /**
     * Test remaining loan amount accessor.
     */
    public function test_remaining_loan_amount_accessor(): void
    {
        $mortgage = MortgageRequest::factory()->create([
            'loan_total_amount' => 800000000,
            'remaining_loan_amount' => 500000000,
        ]);

        $this->assertEquals(500000000, $mortgage->remaining_loan_amount);
    }

    /**
     * Test mortgage request can be created with factory.
     */
    public function test_mortgage_request_can_be_created(): void
    {
        $mortgage = MortgageRequest::factory()->create();

        $this->assertDatabaseHas('mortgage_requests', [
            'id' => $mortgage->id,
        ]);
    }

    /**
     * Test mortgage request uses soft deletes.
     */
    public function test_mortgage_request_uses_soft_deletes(): void
    {
        $mortgage = MortgageRequest::factory()->create();
        $mortgage->delete();

        $this->assertSoftDeleted('mortgage_requests', [
            'id' => $mortgage->id,
        ]);
    }
}
