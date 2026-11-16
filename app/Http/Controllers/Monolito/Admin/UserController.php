<?php

namespace App\Http\Controllers\Monolito\Admin;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\System\Role;

use Illuminate\Http\Request;
use App\Services\QueryService;
use App\Services\PendingUserInvitationService;
use App\Services\StatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\BaseController;
use App\Http\Resources\System\RoleResource;
use App\Http\Requests\Monolito\Admin\StoreUserRequest;
use App\Http\Requests\Monolito\Admin\UpdateUserRequest;
use App\Http\Resources\Api\V1\User\UserResource as UserUserResource;
use App\Http\Resources\System\UserResource; // Usaremos o UserResource que já temos

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Usuários', route('admin.users.index'));

        // O QueryBuilder agora inclui a relação 'roles'
        $excludedRoles = ['Master', 'System Engineering', 'System Developer'];
        $usersQuery = QueryService::query(
            User::class,
            User::ALLOWEDFILTERS(),
            User::ALLOWEDINCLUDES(),
            User::ALLOWEDSORTS(),
            User::DEFAULTSORT(),
            ['roles', 'addresses'],
        );

        // 2. Aplique a condição para excluir usuários com os papéis especificados.
        $usersQuery->whereDoesntHave('roles', function ($query) use ($excludedRoles) {
            $query->whereIn('name', $excludedRoles);
        });

        // 3. Pagine o resultado da consulta já filtrada.
        $users = $usersQuery->paginate($request->get('per_page', 15))->withQueryString();
        $protectedRoles = config('roles.notSelect', []);
        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
            'allRoles' => Role::whereNotIn('name', $protectedRoles)->get(['id', 'name']), // Envia todos os papéis para o seletor de filtro

        ]);
    }

    /**
     * Mostra o formulário para criar um novo usuário.
     */
    public function create(): Response
    {
        $protectedRoles = config('roles.notSelect', []);
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Usuários', route('admin.users.index'))
            ->add('Novo Usuário');
        $roles = Cache::remember('all_roles_list', now()->addHour(), function () use ($protectedRoles) {
            return Role::query()->whereNotIn('name', $protectedRoles)->select('id', 'name')->orderBy('name')->get();
        });

        return Inertia::render('Admin/Users/Create', [
            'roles' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Armazena um novo usuário e envia o convite para completar o cadastro.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $user = app(PendingUserInvitationService::class)->create(
            $validated['name'],
            $validated['email'],
        );

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')->with('flash', [
            'type' => 'success',
            'message' => 'Usuário pré-cadastrado e convite enviado com sucesso.',
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Usuários', route('admin.users.index'))
            ->add($user->name);

        $user->load(['roles', 'addresses']);

        return Inertia::render('Admin/Users/Show', [
            'user' => new UserResource($user),
        ]);
    }

    public function edit(User $user): Response
    {
        $protectedRoles = config('roles.notSelect', []);
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))->add('Usuários', route('admin.users.index'))
            ->add($user->name);

        // Carrega os papéis que o usuário já possui
        $user->load('roles:id,name');

        return Inertia::render('Admin/Users/Edit', [
            'user' => new UserResource($user),
            'allRoles' => Role::whereNotIn('name', $protectedRoles)->get(['id', 'name']),
            //'allRoles' => Role::all(['id', 'name']),
        ]);
    }

    /**
     * Atualiza os dados de um usuário, incluindo seus papéis.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        // O método syncRoles do Spatie é perfeito para sincronizar os papéis
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        return redirect()->route('admin.users.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    /**
     * Ativa ou inativa um usuário.
     */
    public function toggleIsActive(User $user)
    {
        if ($user->id === Auth::User()->id) {
            return redirect()->back()->withErrors(['geral' => 'Você não pode inativar seu próprio usuário.']);
        }

        $motive = 'Alterado pelo Admin'; // Motivo padrão
        if ($user->is_active) {
            StatusService::deactiveSample($user, $motive, Auth::User()->id);
        } else {
            StatusService::activeSample($user, $motive, Auth::User()->id);
        }

        return redirect()->back()->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }

    /**
     * Aprova o registo de um usuário.
     */
    public function approve(User $user)
    {
        $motive = 'Aprovado pelo Admin';
        StatusService::approveSample($user, $motive, Auth::User()->id);

        return redirect()->back()->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }

    /**
     * Rejeita o registo de um usuário.
     */
    public function reject(User $user)
    {
        $motive = 'Rejeitado pelo Admin';
        StatusService::unapproveSample($user, $motive, Auth::User()->id);

        return redirect()->back()->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with(
            'flash',
            [
                'type' => 'success', // ou 'error'
                'message' => 'Ação executada com sucesso.',
            ]
        );
    }

    public function selection(Request $request)
    {
        $users = User::query()
            ->when($request->input('filter.search'), function ($query, $search) {
                // 3. A closure agrupa as condições de busca
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($request->get('per_page', 10));

        return UserUserResource::collection($users);
    }
}
