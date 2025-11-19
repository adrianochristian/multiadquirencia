<?php

namespace Tests\Feature\Api\Withdrawal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateWithdrawalValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_bank_fields_returns_validation_error(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 100.00,
        ]);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
                'details' => [
                    'bank_code',
                    'agency',
                    'account',
                    'holder_name',
                    'holder_document',
                ],
            ],
        ]);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/withdraw', [
            'amount' => 0,
            'bank_code' => '341',
            'agency' => '0001',
            'account' => '12345678',
            'account_type' => 'checking',
            'holder_name' => 'João da Silva',
            'holder_document' => '12345678900',
        ]);

        $response->assertStatus(422);

        $response->assertJson(fn ($json) => $json
            ->where('success', false)
            ->where('error.code', 'VALIDATION_ERROR')
            ->has('error.details.amount')
        );
    }
}

