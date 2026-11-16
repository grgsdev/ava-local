<?php

namespace App\Http\Controllers\Monolito;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;

class AccountDeletionController extends Controller
{
    // Mostra o formulário inicial para pedir o link de exclusão
    public function showRequestForm()
    {
        return Inertia::render('Auth/RequestAccountDeletion');
    }

    // Valida o e-mail e envia o link de confirmação
    public function sendDeletionLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Não conseguimos encontrar um usuário com este endereço de e-mail.']);
        }

        // 1. Criamos um token usando o sistema do Laravel
        $token = Password::broker()->createToken($user);

        // 2. Chamamos o nosso novo método no modelo User para enviar a notificação correta
        $user->sendAccountDeletionLinkNotification($token);
        return redirect()->back()->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Enviamos um e-mail de confirmação. Por favor, verifique a sua caixa de entrada.',
            ]
        );
    }
    // Mostra a página final de confirmação após o usuário clicar no link do e-mail
    public function showConfirmationForm(string $token, Request $request)
    {
        return Inertia::render('Auth/ConfirmAccountDeletion', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    // Apaga de facto a conta do usuário
    public function destroyAccount(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        // 1. Encontramos o usuário ANTES de qualquer coisa
        $user = User::where('email', $request->email)->first();

        // Verificamos se o token é válido para este usuário
        if (!$user || !Password::broker()->tokenExists($user, $request->token)) {
            return back()->withErrors(['email' => 'Token de exclusão inválido ou expirado.']);
        }

        // --- CORREÇÃO APLICADA AQUI ---

        // 2. Apagamos o token PRIMEIRO, usando a variável $user que já temos
        Password::broker()->deleteToken($user);

        // 3. AGORA, apagamos o usuário
        $user->delete();

        return Inertia::render('Auth/DeletionSuccess');
    }
}
