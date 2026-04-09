<?php

namespace Tests\Unit\Services;

use App\Models\Installment;
use App\Models\MortgageRequest;
use App\Models\User;
use App\Services\PaymentService;
use Database\Seeders\RoleAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService;
        $this->seed(RoleAdminSeeder::class);
    }

    /**
     * Test createPayment returns correct Snap token.
     */
    public function test_create_payment_returns_snap_token(): void
    {
        $mockMidtrans = Mockery::mock(\App\Services\MidtransService::class);
        $mockMidtrans->shouldReceive('createSnapToken')
            ->once()
            ->andReturn(['token' => 'test-snap-token-123']);

        $this->app->instance(\App\Services\MidtransService::class, $mockMidtrans);

        $user = User::factory()->create();
        $mortgage = MortgageRequest::factory()->create([
            'user_id' => $user->id,
            'monthly_amount' => 5000000,
        ]);

        $result = $this->service->createPayment($mortgage);

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('test-snap-token-123', $result['token']);
    }

    /**
     * Test createPayment calculates grand total correctly.
     */
    public function test_create_payment_calculates_grand_total(): void
    {
        $mortgage = MortgageRequest::factory()->create([
            'monthly_amount' => 10000000,
        ]);

        $mockMidtrans = Mockery::mock(\App\Services\MidtransService::class);
        $mockMidtrans->shouldReceive('createSnapToken')
            ->with(Mockery::on(function ($params) {
                $expectedMonthly = 10000000;
                $expectedInsurance = 900000;
                $expectedTax = $expectedMonthly * 0.11;
                $expectedTotal = $expectedMonthly + $expectedInsurance + $expectedTax;

                return $params['transaction_details']['gross_amount'] == $expectedTotal;
            }))
            ->once()
            ->andReturn(['token' => 'test-token']);

        $this->app->instance(\App\Services\MidtransService::class, $mockMidtrans);

        $this->service->createPayment($mortgage);
    }

    /**
     * Test processNotification creates installment on successful payment.
     */
    public function test_process_notification_creates_installment(): void
    {
        $mortgage = MortgageRequest::factory()->create([
            'loan_total_amount' => 800000000,
            'remaining_loan_amount' => 800000000,
        ]);

        $notification = [
            'order_id' => 'INSTALLMENT-'.$mortgage->id.'-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
        ];

        $result = $this->service->processNotification($notification);

        $this->assertInstanceOf(Installment::class, $result);
        $this->assertDatabaseHas('installments', [
            'mortgage_request_id' => $mortgage->id,
            'is_paid' => true,
            'payment_type' => 'Midtrans',
        ]);
    }

    /**
     * Test processNotification updates remaining loan amount.
     */
    public function test_process_notification_updates_remaining_loan(): void
    {
        $mortgage = MortgageRequest::factory()->create([
            'loan_total_amount' => 800000000,
            'remaining_loan_amount' => 800000000,
            'monthly_amount' => 10000000,
        ]);

        $notification = [
            'order_id' => 'INSTALLMENT-'.$mortgage->id.'-001',
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];

        $this->service->processNotification($notification);

        $mortgage->refresh();
        $this->assertEquals(790000000, $mortgage->remaining_loan_amount);
    }

    /**
     * Test processNotification ignores non-settlement status.
     */
    public function test_process_notification_ignores_pending_status(): void
    {
        $mortgage = MortgageRequest::factory()->create();

        $notification = [
            'order_id' => 'INSTALLMENT-'.$mortgage->id.'-001',
            'transaction_status' => 'pending',
            'payment_type' => 'credit_card',
        ];

        $result = $this->service->processNotification($notification);

        $this->assertNull($result);
        $this->assertDatabaseCount('installments', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
