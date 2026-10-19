<?php

namespace App\Http\Controllers;

use App\Models\CalculatedPension;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming dashboard request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $calculations = Cache::remember("user:{$user->id}:calculations", 600, function () use ($user) {
            return CalculatedPension::where('user_id', $user->id)
                ->latest()
                ->get()
                ->toArray();
        });

        $documents = Cache::remember("user:{$user->id}:documents", 600, function () use ($user) {
            return Document::where('user_id', $user->id)
                ->with(['recognizedDocument'])
                ->latest()
                ->get()
                ->toArray();
        });

        $taxHistories = Cache::remember("user:{$user->id}:tax_histories", 600, function () use ($user) {
            return $user->taxHistories()
                ->orderBy('year', 'asc')
                ->get()
                ->toArray();
        });

        return Inertia::render('Dashboard', [
            'initialCalculations' => $calculations,
            'initialDocuments' => $documents,
            'initialTaxHistories' => $taxHistories,
        ]);
    }
}
