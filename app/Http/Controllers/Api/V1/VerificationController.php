<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerificationController extends Controller
{
    use ApiResponse;

    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (! $user) {
            return $this->sendError('Usuário não encontrado.', 404);
        }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return $this->sendError('Link de verificação inválido.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->sendSuccess('O e-mail já foi verificado.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $this->sendSuccess('E-mail verificado com sucesso!');
    }
}
