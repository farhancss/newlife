<?php

namespace App\Services;

use App\Mail\StudentInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InvitationMailService
{
    /**
     * Deliver the student invitation immediately. Onboarding credentials must
     * not depend on a queue worker (same requirement as password-reset fixes).
     */
    public function send(User $user, string $temporaryPassword): bool
    {
        try {
            Mail::to($user->email)->send(new StudentInvitationMail($user, $temporaryPassword));

            Log::info('Student invitation email sent.', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Student invitation email failed to send.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
