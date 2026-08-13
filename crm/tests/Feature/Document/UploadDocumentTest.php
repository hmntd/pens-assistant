<?php

namespace Tests\Feature\Document;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_authenticated_user_can_upload_document_and_process_ocr(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('user');

        $file = UploadedFile::fake()->create('income_cert_2024.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->postJson(route('documents.upload'), [
            'file' => $file,
            'document_type' => 'income_certificate',
        ]);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'document' => [
                        'original_filename' => 'income_cert_2024.pdf',
                        'status' => 'completed',
                    ],
                    'recognized' => [
                        'status' => 'success',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'original_filename' => 'income_cert_2024.pdf',
            'status' => 'completed',
        ]);
    }
}
