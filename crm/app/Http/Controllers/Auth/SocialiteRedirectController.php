<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SocialiteRedirectController extends Controller
{
    /**
     * Allowed Socialite OAuth providers.
     *
     * @var array<int, string>
     */
    protected array $allowedProviders = [
        'google',
        'linkedin-openid',
        'linkedin',
        'github',
        'microsoft',
    ];

    /**
     * Redirect the user to the OAuth provider authentication page.
     */
    public function __invoke(string $provider): RedirectResponse
    {
        $config = config('services.google');
        Log::info($config);
        $provider = strtolower($provider);

        if (! in_array($provider, $this->allowedProviders, true)) {
            abort(Response::HTTP_NOT_FOUND, "Unsupported OAuth provider: {$provider}");
        }

        $driver = ($provider === 'microsoft') ? 'azure' : $provider;

        /** @var RedirectResponse $redirect */
        $redirect = Socialite::driver($driver)->redirect();

        return $redirect;
    }
}
