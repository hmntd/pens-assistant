<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\Notification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $changes = array_keys($user->getDirty());

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Audit Log Entry
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'user_profile_updated',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'payload' => [
                'updated_fields' => array_keys($user->getChanges()),
            ],
            'ip_address' => $request->ip(),
        ]);

        // Notification Entry
        Notification::create([
            'user_id' => $user->id,
            'type' => 'success',
            'message' => 'Персональні дані та налаштування профілю успішно оновлено.',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile updated.')]);

        return redirect()->back(fallback: route('profile.edit'));
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
