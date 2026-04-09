<?php

namespace Tests\Unit\Models;

use App\Models\Installment;
use App\Models\MortgageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test installment belongs to mortgage request.
     */
    public function test_installment_belongs_to_mortgage_request(): void
    {
        $mortgage = MortgageRequest::factory()->create();
        $installment = Installment::factory()->create([
            'mortgage_request_id' => $mortgage->id,
        ]);

        $this->assertInstanceOf(MortgageRequest::class, $installment->mortgageRequest);
        $this->assertEquals($mortgage->id, $installment->mortgageRequest->id);
    }

    /**
     * Test installment can be marked as paid.
     */
    public function test_installment_can_be_marked_as_paid(): void
    {
        $installment = Installment::factory()->paid()->create();

        $this->assertTrue($installment->is_paid);
    }

    /**
     * Test installment can be marked as unpaid.
     */
    public function test_installment_can_be_marked_as_unpaid(): void
    {
        $installment = Installment::factory()->unpaid()->create();

        $this->assertFalse($installment->is_paid);
    }

    /**
     * Test installment calculates grand total correctly.
     */
    public function test_installment_calculates_grand_total(): void
    {
        $installment = Installment::factory()->create([
            'sub_total_amount' => 10000000,
            'total_tax_amount' => 1100000,
            'insurance_amount' => 900000,
        ]);

        $expectedGrandTotal = 10000000 + 1100000 + 900000;
        $this->assertEquals($expectedGrandTotal, $installment->grand_total_amount);
    }

    /**
     * Test installment uses soft deletes.
     */
    public function test_installment_uses_soft_deletes(): void
    {
        $installment = Installment::factory()->create();
        $installment->delete();

        $this->assertSoftDeleted('installments', [
            'id' => $installment->id,
        ]);
    }
}
