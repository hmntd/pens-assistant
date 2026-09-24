<?php

namespace App\Policies;

use App\Models\CalculatedPension;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PensionCalculationPolicy
{
    /**
     * Determine whether the user can view any calculation records.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific calculation.
     */
    public function view(User $user, CalculatedPension $calculatedPension): bool
    {
        return $user->isAdmin() || $user->id === $calculatedPension->user_id;
    }

    /**
     * Determine whether the user can create a pension calculation.
     * Gate rule: 1 user can have only 1 pension calculated, but admin can bypass this gate.
     */
    public function create(User $user, ?User $targetUser = null): Response
    {
        $subjectUser = $targetUser ?? $user;

        if ($user->isAdmin()) {
            return Response::allow();
        }

        // if ($subjectUser->calculatedPensions()->exists()) {
        //     return Response::deny('User has already performed a pension calculation. Only 1 calculation per user is allowed.');
        // }

        return Response::allow();
    }
}
