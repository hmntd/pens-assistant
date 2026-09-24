<?php

namespace Tests\Feature\Document;

use App\Models\Document;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_authenticated_user_can_list_their_documents(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Document::create([
            'user_id' => $user->id,
            'file_path' => 'documents/test.pdf',
            'original_filename' => 'test.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson(route('documents.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'user_id', 'original_filename', 'status'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_authenticated_user_can_view_document_details(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $doc = Document::create([
            'user_id' => $user->id,
            'file_path' => 'documents/cert.pdf',
            'original_filename' => 'cert.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->getJson(route('documents.show', ['id' => $doc->id]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $doc->id,
                    'original_filename' => 'cert.pdf',
                ],
            ]);
    }

    public function test_authenticated_user_can_delete_their_document(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $doc = Document::create([
            'user_id' => $user->id,
            'file_path' => 'documents/del.pdf',
            'original_filename' => 'del.pdf',
            'document_type' => 'income_certificate',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($user)->deleteJson(route('documents.destroy', ['id' => $doc->id]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'status' => 'success',
                'message' => 'Document deleted successfully',
            ]);

        $this->assertSoftDeleted('documents', ['id' => $doc->id]);
    }
}
