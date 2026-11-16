<?php

namespace App\Http\Controllers\Monolito\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegistrationCompletionController extends Controller
{
    /**
     * Mostra o formulário para o usuário completar o cadastro.
     */
    public function show(Request $request, User $user)
    {
        $this->ensurePendingInvitation($user);

        return Inertia::render('Auth/CompleteRegistration', [
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Processa a finalização do cadastro.
     */
    public function store(Request $request, User $user)
    {
        $this->ensurePendingInvitation($user);

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone_number' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
            // Adicione outras validações para os campos que o usuário deve preencher
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'birth_date' => $request->birth_date,
            'email_verified_at' => now(),
            'verification_code' => null, // Invalida o token
            'verification_sent_at' => null,
            'is_active' => true,
        ])->save();

        Auth::login($user);

        return redirect()->route('dashboard')->with('flash', [
            'type' => 'success',
            'message' => 'Cadastro finalizado com sucesso! Bem-vindo(a).',
        ]);
    }

    private function ensurePendingInvitation(User $user): void
    {
        abort_unless($user->email_verified_at === null && ! $user->is_active, 403);
    }
}
