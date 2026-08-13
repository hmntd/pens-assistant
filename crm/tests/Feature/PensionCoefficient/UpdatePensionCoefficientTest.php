<?php

namespace Tests\Feature\PensionCoefficient;

use Tests\TestCase;

class UpdatePensionCoefficientTest extends TestCase
{
    public function test_can_update_pension_coefficient(): void
    {
        $createResponse = $this->postJson(route('pension-coefficients.store'), [
            'year' => 2029,
            'month' => 6,
            'coefficient' => 1.100,
            'description' => 'Initial',
        ]);
        
        $id = $createResponse->json('data.id') ?? 1;

        $updateResponse = $this->putJson(route('pension-coefficients.update', ['id' => $id]), [
            'year' => 2029,
            'month' => 6,
            'coefficient' => 1.115,
            'description' => 'Updated',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $id,
                    'coefficient' => 1.115,
                    'description' => 'Updated',
                ],
            ]);
    }
}
