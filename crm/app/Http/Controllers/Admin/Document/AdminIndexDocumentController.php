<?php

namespace App\Http\Controllers\Admin\Document;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Document\AdminIndexDocumentRequest;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdminIndexDocumentController extends Controller
{
    /**
     * Display a paginated listing of user documents.
     */
    public function __invoke(AdminIndexDocumentRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Document::class);

        $validated = $request->validated();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $search = (string) ($validated['search'] ?? '');
        $documentType = (string) ($validated['document_type'] ?? '');
        $sortBy = (string) ($validated['sort_by'] ?? 'created_at');
        $sortDir = strtolower((string) ($validated['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['id', 'created_at', 'document_type', 'status'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query = Document::with('user');

        if (! empty($documentType)) {
            $query->where('document_type', $documentType);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderBy($sortBy, $sortDir);

        $paginated = $query->paginate($perPage);

        $transformed = collect($paginated->items())->map(function (Document $doc): array {
            /** @var User|null $owner */
            $owner = $doc->user;
            $fullPath = storage_path("app/{$doc->file_path}");
            $fileSizeBytes = file_exists($fullPath) ? (int) filesize($fullPath) : 0;

            return [
                'id' => $doc->id,
                'user_id' => $doc->user_id,
                'user_name' => $owner ? $owner->name : 'User',
                'user_email' => $owner ? $owner->email : 'N/A',
                'user' => $owner ? [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                ] : null,
                'title' => $doc->original_filename,
                'original_filename' => $doc->original_filename,
                'document_type' => $doc->document_type,
                'file_size' => $fileSizeBytes,
                'formatted_file_size' => $fileSizeBytes > 1024 * 1024
                    ? round($fileSizeBytes / (1024 * 1024), 2).' MB'
                    : round($fileSizeBytes / 1024, 2).' KB',
                'status' => $doc->status,
                'created_at' => $doc->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $paginated->currentPage(),
                'data' => $transformed,
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
