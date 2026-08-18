<?php

namespace App\Http\Controllers;

use App\Models\CalculatedPension;
use App\Models\Document;

use Illuminate\Http\Request;
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

        $calculations = CalculatedPension::where('user_id', $user->id)
            ->latest()
            ->get();

        $documents = Document::where('user_id', $user->id)
            ->latest()
            ->get();

        $taxHistories = $user->taxHistories()
            ->orderBy('year', 'asc')
            ->get();

        return Inertia::render('Dashboard', [
            'initialCalculations' => $calculations,
            'initialDocuments' => $documents,
            'initialTaxHistories' => $taxHistories,
        ]);
    }
}
