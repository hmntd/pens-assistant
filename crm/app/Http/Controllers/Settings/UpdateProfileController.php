<?php

namespace App\Http\Controllers\Settings;

use App\Events\UserProfileUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UpdateProfileController extends Controller
{
    /**
     * Update the user's profile information.
     */
    public function __invoke(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $validated = $request->validated();
        if (! empty($validated['gender'])) {
            $validated['gender'] = strtoupper((string) $validated['gender']);
        } else {
            $validated['gender'] = null;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Audit Log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'payload' => [
                'updated_fields' => array_keys($user->getChanges()),
            ],
            'ip_address' => $request->ip(),
        ]);

        // Dispatch domain event for notification handling
        event(new UserProfileUpdated($user));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return redirect()->back(fallback: route('profile.edit'));
    }
}
