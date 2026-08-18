<?php

namespace App\Http\Controllers;

use App\Models\PfuNews;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Display the welcome landing page with latest PFU news.
     */
    public function __invoke(): Response
    {
        $pfuNews = PfuNews::latest()->take(3)->get();

        return Inertia::render('Welcome', [
            'pfuNews' => $pfuNews,
        ]);
    }
}
