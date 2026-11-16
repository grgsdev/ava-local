<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\StoreTeamInvitationRequest;
use App\Notifications\TeamInvitationNotification;

class TeamController extends BaseController
{
    public function index()
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Equipe', '');

        $company = app('current_company');

        // Carrega os utilizadores relacionados com a empresa atual
        $users = $company->users()->with('companies')->paginate(10);

        return Inertia::render('Team/Index', [
            'teamMembers' => UserResource::collection($users),
        ]);
    }

    public function storeInvitation(StoreTeamInvitationRequest $request)
    {
        $company = app('current_company');

        // Encontra um utilizador pelo email ou cria um novo com uma senha aleatória
        $user = User::firstOrCreate(
            ['email' => $request->input('email')],
            [
                'name' => $request->input('name'),
                'password' => Hash::make(Str::random(12)),
            ]
        );

        // Adiciona ou atualiza o perfil do utilizador na empresa atual
        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => $request->input('role')]
        ]);
        $user->notify(new TeamInvitationNotification($company));
        return redirect()->route('team.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Usuário convidado com sucesso.',
            ]
        );
    }

    public function updateMember(Request $request, User $user)
    {
        $company = app('current_company');

        // Validação simples para o perfil
        $validated = $request->validate([
            'role' => 'required|string|in:Editor,Manager',
        ]);

        // Garante que o utilizador a ser atualizado pertence à empresa atual
        if (!$company->users()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        // Atualiza o perfil na tabela-pivô
        $company->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        return redirect()->route('team.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Perfil do usuário atualizado com sucesso.',
            ]
        );
    }

    public function destroyMember(User $user)
    {
        $company = app('current_company');

        // Garante que o utilizador a ser removido pertence à empresa atual
        if (!$company->users()->where('user_id', '!=', auth()->id())->where('user_id', $user->id)->exists()) {
            // Adicionamos a condição de que um admin não se pode remover a si mesmo
            abort(403);
        }

        // Desvincula o utilizador da empresa
        $company->users()->detach($user->id);

        return redirect()->route('team.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Usuário removido com sucesso.',
            ]
        );
    }

    public function deactivateMember(User $user)
    {
        $company = app('current_company');
        // Lógica de segurança para garantir que o utilizador a ser desativado pertence à equipe
        // e que um admin não se pode desativar a si mesmo.
        if ($user->id === auth()->id() || !$company->users()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        // Atualiza o status na tabela-pivô para 'inactive'
        $company->users()->updateExistingPivot($user->id, ['status' => 'inactive']);

        return redirect()->route('team.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Usuário desativado com sucesso.',
            ]
        );
    }

    public function reactivateMember(User $user)
    {
        $company = app('current_company');
        if (!$company->users()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        // Atualiza o status na tabela-pivô para 'active'
        $company->users()->updateExistingPivot($user->id, ['status' => 'active']);

        return redirect()->route('team.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Usuário reativado com sucesso.',
            ]
        );
    }
}
