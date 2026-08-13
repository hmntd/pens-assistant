<?php

namespace Tests\Feature\PensionCoefficient;

use Tests\TestCase;

class DeletePensionCoefficientTest extends TestCase
{
    public function test_can_delete_pension_coefficient(): void
    {
        $createResponse = $this->postJson(route('pension-coefficients.store'), [
            'year' => 2030,
            'month' => 7,
            'coefficient' => 1.120,
            'description' => 'To be deleted',
        ]);

        $id = $createResponse->json('data.id') ?? 1;

        $deleteResponse = $this->deleteJson(route('pension-coefficients.destroy', ['id' => $id]));

        $deleteResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pension coefficient deleted successfully',
            ]);
    }
}
