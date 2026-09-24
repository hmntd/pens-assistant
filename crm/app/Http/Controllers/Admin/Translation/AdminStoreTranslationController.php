<?php

namespace App\Http\Controllers\Admin\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Translation\AdminStoreTranslationRequest;
use App\Services\TranslationManagerService;
use Illuminate\Http\JsonResponse;

class AdminStoreTranslationController extends Controller
{
    /**
     * Store a new translation key and its locale values.
     */
    public function __invoke(AdminStoreTranslationRequest $request, TranslationManagerService $service): JsonResponse
    {
        $validated = $request->validated();

        $key = trim($validated['key']);
        $ukVal = $validated['uk'];
        $enVal = $validated['en'];

        $service->saveTranslationKey($key, $ukVal, $enVal);

        return response()->json([
            'status' => 'success',
            'message' => "Translation for key \"{$key}\" created successfully.",
            'data' => [
                'key' => $key,
                'uk' => $ukVal,
                'en' => $enVal,
            ],
        ]);
    }
}
