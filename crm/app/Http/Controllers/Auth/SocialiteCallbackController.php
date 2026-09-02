<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteCallbackController extends Controller
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
     * Handle the OAuth provider callback.
     */
    public function __invoke(string $provider): RedirectResponse
    {
        $provider = strtolower($provider);

        if (! in_array($provider, $this->allowedProviders, true)) {
            abort(Response::HTTP_NOT_FOUND, "Unsupported OAuth provider: {$provider}");
        }

        $driver = ($provider === 'microsoft') ? 'azure' : $provider;

        try {
            /** @var \Laravel\Socialite\Contracts\User $socialUser */
            $socialUser = Socialite::driver($driver)->user();
        } catch (\Throwable $e) {
            Log::error("Socialite callback failed for provider {$provider}: {$e->getMessage()}");

            return redirect()->route('login')->withErrors([
                'email' => 'Failed to authenticate via ' . ucfirst($provider) . '. Please try again.',
            ]);
        }

        $email = $socialUser->getEmail();
        $providerId = (string) $socialUser->getId();

        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your ' . ucfirst($provider) . ' account does not provide an email address.',
            ]);
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'provider_name' => $provider,
                    'provider_id' => $providerId,
                    'avatar' => $user->avatar ?: $socialUser->getAvatar(),
                ]);

                if (is_null($user->email_verified_at)) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }
            }
        }

        if (! $user) {
            $fullName = trim((string) $socialUser->getName());
            if (! empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
            } else {
                $firstName = Str::headline(Str::before($email, '@'));
                $lastName = '';
            }

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'provider_name' => $provider,
                'provider_id' => $providerId,
                'avatar' => $socialUser->getAvatar(),
            ]);

            $user->assignRole('user');
        }

        if ($user->is_suspended) {
            return redirect()->route('login')->withErrors([
                'email' => __('Your account has been suspended. Please contact support.'),
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
