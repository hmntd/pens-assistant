<?php

namespace App\Http\Controllers\Admin\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Translation\AdminIndexTranslationRequest;
use App\Services\TranslationManagerService;
use Illuminate\Http\JsonResponse;

class AdminIndexTranslationController extends Controller
{
    /**
     * Display listing of translation keys with UK and EN values.
     */
    public function __invoke(AdminIndexTranslationRequest $request, TranslationManagerService $service): JsonResponse
    {
        $validated = $request->validated();
        $translations = $service->getAllTranslations();

        // Search filter
        if (! empty($validated['search'])) {
            $searchLower = mb_strtolower((string) $validated['search']);
            $translations = array_filter($translations, function ($item) use ($searchLower) {
                return str_contains(mb_strtolower($item['key']), $searchLower)
                    || str_contains(mb_strtolower($item['uk']), $searchLower)
                    || str_contains(mb_strtolower($item['en']), $searchLower);
            });
            $translations = array_values($translations);
        }

        $perPage = max(1, min(200, (int) ($validated['per_page'] ?? 25)));
        $page = max(1, (int) ($validated['page'] ?? 1));
        $total = count($translations);
        $offset = ($page - 1) * $perPage;

        $pagedItems = array_slice($translations, $offset, $perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'items' => $pagedItems,
            ],
        ]);
    }
}
