<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PixAndWithdrawalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_pix_for_authenticated_user(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'amount' => 125.50,
            'description' => 'Pagamento via PIX',
            'customer_name' => 'João da Silva',
            'customer_document' => '12345678900',
        ]);

        $response->assertCreated();

        $response->assertJsonStructure([
            'success',
            'data' => [
                'transaction_id',
                'external_id',
                'amount',
                'status',
                'qr_code',
                'qr_code_url',
                'created_at',
            ],
        ]);
    }

    public function test_can_create_withdrawal_for_authenticated_user(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 500.00,
            'bank_code' => '341',
            'agency' => '0001',
            'account' => '12345678',
            'account_type' => 'checking',
            'holder_name' => 'João da Silva',
            'holder_document' => '12345678900',
        ]);

        $response->assertCreated();

        $response->assertJsonStructure([
            'success',
            'data' => [
                'withdrawal_id',
                'external_id',
                'amount',
                'status',
                'requested_at',
                'created_at',
            ],
        ]);
    }
}

