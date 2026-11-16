<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Mail\ForgotPasswordVerificationCodeMail;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;

/**
 * @group API-MOBILE Autenticação
 * @version V1
 * @description Endpoints para gerenciar a autenticação de usuários da API
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Registrar um novo usuário
     * @unauthenticated
     */
    public function register(RegisterRequest $request)
    {

        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $referredByUserId = null;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'birth_date' => $request->birth_date,
            'phone_number' => $request->phone_number,
            'verification_code'    => $code,
            'verification_sent_at' => now(),
        ]);

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        $user->assignRole('Panel User');
        Mail::to($user->email)
            ->send(new VerificationCodeMail($code, $user->name));

        // event(new Registered($user)); // Dispara o evento que envia o e-mail de verificação

        return $this->sendSuccess('Registro bem-sucedido! Por favor, verifique seu e-mail para ativar sua conta.');
    }




    /**
     * Fazer login e obter um token Sanctum
     * @unauthenticated
     */

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            return $this->sendError('Seu e-mail ainda não foi verificado.', 403);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        $user->load(['roles', 'addresses']);

        $userData = new UserResource($user);

        return $this->sendResponse(
            ['token' => $token, 'user' => $userData],
            'Login bem-sucedido.'
        );
    }
    /**
     * Obter dados do usuário autenticado
     * @authenticated
     */
    public function user(Request $request)
    {
        $user = $request->user();

        // 1. Carregamos as relações base que sempre queremos
        $user->load(['roles', 'addresses']);

        // 3. Retornamos o UserResource, que já sabe como lidar com as relações carregadas
        return new UserResource($user);
    }

    /**
     * Atualizar o perfil do usuário autenticado
     * @authenticated
     */
    public function update(UpdateProfileRequest $request)
    {
        // O FormRequest já validou os dados
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($user, $validated, $request) {
            // 1. Atualiza os dados principais do usuário
            $user->update($validated);

            // 2. Lida com o upload do avatar
            if ($request->hasFile('avatar')) {
                $user->clearMediaCollection('avatar');
                $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }

            // --- LÓGICA DE ENDEREÇO "UPDATE OR CREATE" ---
            // 3. Verifica se os dados do endereço foram enviados
            if (isset($validated['address'])) {
                // O método 'updateOrCreate' do Eloquent é perfeito para isto.
                // Ele irá procurar um endereço para este usuário com o tipo 'main'.
                // Se encontrar, atualiza os dados. Se não, cria um novo.
                $user->addresses()->updateOrCreate(
                    [
                        'type' => 'main' // A condição para encontrar o endereço
                    ],
                    $validated['address'] // Os novos dados para atualizar ou criar
                );
            }
        });

        // Carregamos as relações atualizadas antes de devolver a resposta
        $user->load('addresses');

        return new UserResource($user);
    }

    /**
     * Fazer logout (revogar o token atual)
     * @authenticated
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendSuccess('Logout bem-sucedido.');
    }


    /**
     * Altera a senha do usuário autenticado.
     * @authenticated
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // A validação já foi feita pelo ChangePasswordRequest.
        // Se chegámos aqui, a senha atual está correta.

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->sendSuccess('Senha alterada com sucesso!');
    }

    /**
     * Verifica código de email
     * @unauthenticated
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code'  => 'required|digits:4',
        ]);

        $email = $request->email;
        $user = User::where('email', $email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json([
                'code'    => 'ACCOUNT_NOT_FOUND_OR_VERIFIED',
                'message' => 'Conta não encontrada ou já verificada.',
            ], 404);
        }

        if (!$user->verification_sent_at || $user->verification_sent_at->lt(now()->subHours(36))) {
            Log::warning('Verification code expired', ['user_id' => $user->id]);
            //       $user->delete();
            return response()->json(['message' => 'Código expirado. Cadastre‑se novamente.'], 410);
        }

        $expected = (string) $user->verification_code;
        $given    = str_pad((string) $request->input('code'), 4, '0', STR_PAD_LEFT);

        if (!hash_equals($expected, $given)) {
            return response()->json(['message' => 'Código inválido.'], 401);
        }

        $user->forceFill([
            'email_verified_at'         => now(),
            'verification_code'         => null,
            'verification_resend_count' => null,
            'verification_sent_at'      => null,
            'is_active' => true,
        ])->save();


        return $this->sendSuccess('Registrado com sucesso');
    }

    /**
     * Reenvia código de email
     * @unauthenticated
     */
    public function resendVerificationCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->input('email');

        $user = User::where('email', $email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Conta não encontrada ou já verificada.'], 404);
        }

        $sendCount    = $user->verification_resend_count ?? 0;
        $withinWindow = $user->verification_sent_at
            && $user->verification_sent_at->gt(now()->subHours(36));

        if ($sendCount >= 3 && $withinWindow) {
            return response()->json(['message' => 'Limite de reenvio atingido. Aguarde algumas horas.'], 429);
        }

        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $user->forceFill([
            'verification_code'         => $code,
            'verification_sent_at'      => now(),
            'verification_resend_count' => $withinWindow ? $sendCount + 1 : 1,
        ])->save();

        Mail::to($user->email)
            ->send(new VerificationCodeMail($code, $user->name));

        return response()->json(['message' => 'Código reenviado!'], 200);
    }


    /**
     * Esqueci minha senha
     * @unauthenticated
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->input('email');

        $user = User::where('email', $email)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Conta não encontrada.'], 404);
        }
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $user->forceFill([
            'verification_code'         => $code,

        ])->save();

        Mail::to($user->email)
            ->send(new ForgotPasswordVerificationCodeMail($code, $user->name));

        return $this->sendSuccess('Solicitação bem-sucedida! Por favor, verifique seu e-mail para prosseguir com a alteração de senha.');
    }

    /**
     * Verifica Esqueci minha senha
     * @unauthenticated
     */
    public function verifyForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code'  => 'required|digits:4',
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        $email = $request->email;
        $user = User::where('email', $email)
            ->first();

        if (!$user) {
            return response()->json([
                'code'    => 'ACCOUNT_NOT_FOUND_OR_VERIFIED',
                'message' => 'Conta não encontrada.',
            ], 404);
        }


        $expected = (string) $user->verification_code;
        $given    = str_pad((string) $request->input('code'), 4, '0', STR_PAD_LEFT);

        if (!hash_equals($expected, $given)) {
            return response()->json(['message' => 'Código inválido.'], 401);
        }

        $user->forceFill([
            'verification_code'         => null,
            'verification_resend_count' => null,
            'verification_sent_at'      => null,
            'password' => Hash::make($request->password),

        ])->save();


        return $this->sendSuccess('Senha alterada com sucesso');
    }

    /**
     * Apaga a conta do usuário autenticado após confirmar a senha.
     * @authenticated
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verifica se a senha fornecida está correta
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        // A senha está correta, podemos prosseguir com a exclusão
        // Primeiro, revogamos todos os tokens do usuário
        $user->tokens()->delete();

        // Aqui entra a sua lógica de negócio: soft delete, anonimização, etc.
        $user->delete();

        return $this->sendSuccess('Sua conta foi apagada com sucesso.');
    }
}
