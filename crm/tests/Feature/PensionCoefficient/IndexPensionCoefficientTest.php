<?php

namespace Tests\Feature\PensionCoefficient;

use Tests\TestCase;

class IndexPensionCoefficientTest extends TestCase
{
    public function test_can_list_pension_coefficients_with_pagination(): void
    {
        $response = $this->getJson(route('pension-coefficients.index', ['page' => 1, 'per_page' => 5]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'year', 'month', 'coefficient', 'description'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson([
                'status' => 'success',
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 5,
                ],
            ]);
    }
}
