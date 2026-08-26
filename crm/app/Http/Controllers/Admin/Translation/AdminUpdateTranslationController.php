<?php

namespace App\Http\Controllers\Admin\Translation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Translation\AdminUpdateTranslationRequest;
use App\Services\TranslationManagerService;
use Illuminate\Http\JsonResponse;

class AdminUpdateTranslationController extends Controller
{
    /**
     * Update an existing translation key values.
     */
    public function __invoke(AdminUpdateTranslationRequest $request, TranslationManagerService $service): JsonResponse
    {
        $validated = $request->validated();

        $key = trim($validated['key']);
        $ukVal = $validated['uk'] ?? '';
        $enVal = $validated['en'] ?? '';

        $service->saveTranslationKey($key, $ukVal, $enVal);

        return response()->json([
            'status' => 'success',
            'message' => "Translation for key \"{$key}\" updated successfully.",
            'data' => [
                'key' => $key,
                'uk' => $ukVal,
                'en' => $enVal,
            ],
        ]);
    }
}
