<?php

namespace Tests\Feature\PensionCoefficient;

use Tests\TestCase;

class StorePensionCoefficientTest extends TestCase
{
    public function test_can_store_pension_coefficient(): void
    {
        $payload = [
            'year' => 2028,
            'month' => 5,
            'coefficient' => 1.095,
            'description' => 'Травень 2028 - Прогноз',
        ];

        $response = $this->postJson(route('pension-coefficients.store'), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'year' => 2028,
                    'month' => 5,
                    'coefficient' => 1.095,
                    'description' => 'Травень 2028 - Прогноз',
                ],
            ]);
    }

    public function test_store_validation_fails_for_invalid_month(): void
    {
        $payload = [
            'year' => 2028,
            'month' => 15,
            'coefficient' => 1.095,
        ];

        $response = $this->postJson(route('pension-coefficients.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['month']);
    }
}
