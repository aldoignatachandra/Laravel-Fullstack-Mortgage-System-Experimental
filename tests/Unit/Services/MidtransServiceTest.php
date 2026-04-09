<?php

namespace Tests\Unit\Services;

use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    use RefreshDatabase;

    private MidtransService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MidtransService;
    }

    /**
     * Test createSnapToken returns token from Midtrans API.
     */
    public function test_create_snap_token_returns_token(): void
    {
        $params = [
            'transaction_details' => [
                'order_id' => 'TEST-ORDER-123',
                'gross_amount' => 10000000,
            ],
            'customer_details' => [
                'first_name' => 'Test',
                'email' => 'test@example.com',
                'phone' => '081234567890',
            ],
        ];

        $this->markTestSkipped('Requires Midtrans API mocking or sandbox environment');
    }

    /**
     * Test handleNotification parses webhook correctly.
     */
    public function test_handle_notification_parses_webhook(): void
    {
        $notification = new \stdClass;
        $notification->order_id = 'INSTALLMENT-123-001';
        $notification->transaction_status = 'settlement';
        $notification->payment_type = 'credit_card';

        $result = $this->service->handleNotification($notification);

        $this->assertEquals('INSTALLMENT-123-001', $result['order_id']);
        $this->assertEquals('settlement', $result['transaction_status']);
        $this->assertEquals('credit_card', $result['payment_type']);
    }

    /**
     * Test handleNotification handles invalid input.
     */
    public function test_handle_notification_handles_invalid_input(): void
    {
        $this->expectException(\Exception::class);

        $invalidNotification = null;
        $this->service->handleNotification($invalidNotification);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
