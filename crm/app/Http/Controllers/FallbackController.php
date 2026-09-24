<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class FallbackController extends Controller
{
    /**
     * Handle fallback routes by rendering custom 404 error page.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Error', ['status' => 404])
            ->toResponse($request)
            ->setStatusCode(404);
    }
}
