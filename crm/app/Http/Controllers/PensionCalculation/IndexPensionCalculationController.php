<?php

namespace App\Http\Controllers\PensionCalculation;

use App\Http\Controllers\Controller;
use App\Models\CalculatedPension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class IndexPensionCalculationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        Gate::authorize('viewAny', CalculatedPension::class);

        if ($user->isAdmin()) {
            $records = CalculatedPension::with('user:id,first_name,last_name,email')
                ->latest()
                ->get();
        } else {
            $records = Cache::remember("user:{$user->id}:calculations", 600, function () use ($user) {
                return CalculatedPension::with('user:id,first_name,last_name,email')
                    ->where('user_id', $user->id)
                    ->latest()
                    ->get()
                    ->toArray();
            });
        }

        return response()->json([
            'data' => $records,
        ], Response::HTTP_OK);
    }
}
