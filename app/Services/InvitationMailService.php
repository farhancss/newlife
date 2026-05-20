<?php

namespace App\Services;

use App\Mail\StudentInvitationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class InvitationMailService
{
    public function send(User $user, string $temporaryPassword): void
    {
        Mail::to($user->email)->queue(new StudentInvitationMail($user, $temporaryPassword));
    }
}
