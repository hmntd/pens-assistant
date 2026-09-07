<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminAuthCheckController extends Controller
{
    /**
     * Handle the incoming admin auth check request for Nginx subrequest authentication.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('admin')) {
            return response('OK', Response::HTTP_OK);
        }

        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }
}
