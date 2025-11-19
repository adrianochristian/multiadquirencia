<?php

namespace Tests\Feature\Api\Pix;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreatePixValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_amount_is_required(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'description' => 'Teste sem amount',
        ]);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
                'details' => [
                    'amount',
                ],
            ],
        ]);
    }

    public function test_amount_must_be_positive(): void
    {
        $this->seed();

        $user = User::first();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pix', [
            'amount' => -10,
            'description' => 'Valor negativo',
        ]);

        $response->assertStatus(422);

        $response->assertJson(fn ($json) => $json
            ->where('success', false)
            ->where('error.code', 'VALIDATION_ERROR')
            ->has('error.details.amount')
        );
    }
}

