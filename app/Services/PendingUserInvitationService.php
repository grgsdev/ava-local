<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\InviteUserToCompleteRegistration;

class PendingUserInvitationService
{
    public function create(string $name, string $email): User
    {
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'verification_code' => $code,
            'verification_sent_at' => now(),
        ]);

        $user->notify(new InviteUserToCompleteRegistration());

        return $user;
    }
}
