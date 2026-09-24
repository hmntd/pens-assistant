<?php

namespace Tests\Feature\Admin;

use App\Models\Document;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

class AdminDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_document_statuses_summary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        Document::create([
            'user_id' => $user->id,
            'file_path' => 'documents/doc1.pdf',
            'original_filename' => 'doc1.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.documents.statuses'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'total_documents' => 1,
                    'statuses' => [
                        'completed' => 1,
                    ],
                ],
            ]);
    }

    public function test_admin_can_list_all_user_documents(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        Document::create([
            'user_id' => $user->id,
            'file_path' => 'documents/doc2.pdf',
            'original_filename' => 'doc2.pdf',
            'document_type' => 'income_certificate',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.documents.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertEquals(1, count($response->json('data.data')));
    }

    public function test_admin_can_show_any_document(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $regularUser = User::factory()->create();
        $doc = Document::create([
            'user_id' => $regularUser->id,
            'file_path' => 'documents/doc3.pdf',
            'original_filename' => 'doc3.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.documents.show', ['id' => $doc->id]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $doc->id,
                    'document_type' => 'income_certificate',
                    'status' => 'completed',
                ],
            ]);
    }

    public function test_regular_user_cannot_access_admin_document_endpoints(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $response = $this->actingAs($regularUser)->getJson(route('admin.documents.statuses'));
        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $responseIndex = $this->actingAs($regularUser)->getJson(route('admin.documents.index'));
        $responseIndex->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
