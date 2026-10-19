<?php

namespace App\Http\Controllers;

use App\Models\PfuNews;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Display the welcome landing page with latest PFU news.
     */
    public function __invoke(): Response
    {
        $pfuNews = Cache::remember(PfuNews::CACHE_KEY_LATEST, 3600, function () {
            return PfuNews::latest()->take(3)->get()->toArray();
        });

        return Inertia::render('Welcome', [
            'pfuNews' => $pfuNews,
        ]);
    }
}
