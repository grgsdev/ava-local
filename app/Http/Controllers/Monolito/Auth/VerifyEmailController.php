<?php

namespace App\Http\Controllers\Monolito\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if (Auth::User()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        if (Auth::User()->markEmailAsVerified()) {
            event(new Verified(Auth::User()));
        }

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
