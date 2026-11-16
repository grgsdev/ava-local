<?php

namespace App\Http\Controllers\Monolito\System;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Services\QueryService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\System\UserResource;
use App\Http\Requests\System\StoreUserRequest;
use App\Http\Requests\System\UpdateUserRequest;

class SystemDeveloperController extends BaseController
{
    public function index(Request $request)
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Users'), '');

        $users = QueryService::query(
            // A query base já filtra pelo papel 'System Developer'
            User::whereHas('roles', fn($q) => $q->whereIn('name', ['System Engineering', 'System Developer'])),
            User::ALLOWEDFILTERS(),
            [],
            User::ALLOWEDSORTS()
        )
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return Inertia::render('System/Users/Index', [
            'users' => UserResource::collection($users),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
        ]);
        // return Inertia::render('UnderConstruction', [
        //     'featureTitle' => 'Documentos do Projeto' // Passamos o nome da funcionalidade
        // ]);
    }

    public function create(): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Users'), route('system.users.index'))->add(__('Add'), '');

        return Inertia::render('System/Users/Create');
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('System Developer');

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('system.users.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }

    /**
     * Mostra o formulário para editar um usuário existente.
     */
    public function edit(User $user): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Users'), route('system.users.index'))->add(__('Edit') . ' ' . $user->name, '');

        return Inertia::render('System/Users/Edit', [
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Atualiza o usuário no banco de dados.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // Pega os dados validados, exceto o avatar que será tratado separadamente
        $validatedData = $request->except('avatar', 'password');

        // Se uma nova senha foi enviada, atualiza
        if ($request->filled('password')) {
            $validatedData['password'] = Hash::make($request->password);
        }

        $user->update($validatedData);

        // Se um novo avatar foi enviado, apaga o antigo e adiciona o novo
        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('system.users.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    /**
     * Remove o usuário do banco de dados.
     */
    public function destroy(User $user)
    {
        if (Auth::user()->cannot('delete system_developers')) {
            abort(403, 'Ação não autorizada.');
        }

        // Garante que um usuário não possa apagar a si mesmo
        if ($user->id === Auth::user()->id()) {
            return redirect()->back()->withErrors(['geral' => 'Você não pode apagar seu próprio usuário.']);
        }

        $user->delete();

        return redirect()->route('system.users.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }
}
