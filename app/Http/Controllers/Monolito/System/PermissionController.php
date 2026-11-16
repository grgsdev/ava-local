<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\System\Role;
use Illuminate\Http\Request;
use App\Services\QueryService;
use App\Models\System\Permission;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\BreadcrumbsManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\BaseController;
use App\Http\Resources\System\RoleResource;
use App\Http\Resources\System\PermissionResource;
use App\Http\Requests\System\StorePermissionRequest;
use App\Http\Requests\System\UpdatePermissionRequest;

class PermissionController extends BaseController
{

    /**
     * Verifica se uma permissão é protegida pela configuração.
     *
     * @param Permission $permission
     * @return bool
     */
    private function isProtected(Permission $permission): bool
    {
        return in_array($permission->name, config('permissions.protected', []));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Permissions'), '');

        $protectedPermissions = config('permissions.protected', []);
        // Usando nosso QueryService com o model de Permissão da Spatie
        $permissions = QueryService::query(
            Permission::class,
            Permission::ALLOWEDFILTERS(),
            [], // Sem includes por enquanto
            Permission::ALLOWEDSORTS()
        )
            ->whereNotIn('name', $protectedPermissions)
            ->withCount('roles')
            ->paginate($request->get('per_page', 15))->withQueryString();
        // Renderiza a página Vue e passa os dados
        return Inertia::render('System/Permissions/Index', [
            'permissions' => PermissionResource::collection($permissions),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
        ]);
    }

    /**
     * Mostra o formulário para criar uma nova permissão.
     */
    public function create(): Response
    {
        $protectedRoles = config('roles.protected', []);

        $roles = Cache::remember('all_roles_list', now()->addHour(), function () use ($protectedRoles) {
            return Role::query()->whereNotIn('name', $protectedRoles)->select('id', 'name')->orderBy('name')->get();
        });
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Permissions'), route('system.permissions.index'))->add(__('Add'), '');

        return Inertia::render('System/Permissions/Create', [
            'roles' => RoleResource::collection($roles), // Enviamos para o frontend
        ]);
    }

    /**
     * Salva a nova permissão no banco de dados.
     */
    public function store(StorePermissionRequest $request)
    {
        // O FormRequest já validou os dados, então podemos usá-los com segurança.
        $permission = Permission::create($request->validated());
        if ($request->has('roles')) {
            $permission->syncRoles($request->roles);
        }
        Cache::forget('all_permissions_list');
        Cache::forget('all_roles_list');


        // activity()
        //     ->performedOn($permission) // O objeto que sofreu a ação
        //     ->causedBy(auth()->user()) // O usuário que causou a ação
        //     ->log("A permissão foi adicionada");
        return redirect()->route('system.permissions.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    /**
     * Mostra o formulário para editar uma permissão existente.
     */
    public function edit(Permission $permission): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Permissions'), route('system.permissions.index'))->add(__('Edit') . ' ' . $permission->name, '');

        if ($this->isProtected($permission)) {
            abort(403, 'Esta permissão é protegida e não pode ser editada.');
        }
        $protectedRoles = config('roles.protected', []);
        $permission->load('roles:id,name');
        $allRoles = Cache::remember('all_roles_list', now()->addHour(), function () use ($protectedRoles) {
            return Role::query()->whereNotIn('name', $protectedRoles)->select('id', 'name')->orderBy('name')->get();
        });

        return Inertia::render('System/Permissions/Edit', [
            'permission' => new PermissionResource($permission),
            'allRoles' => RoleResource::collection($allRoles),
        ]);
    }

    /**
     * Atualiza a permissão no banco de dados.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        if ($this->isProtected($permission)) {
            abort(403, 'Esta permissão é protegida e não pode ser editada.');
        }
        $permission->update($request->validated());
        $permission->syncRoles($request->input('roles', []));
        Cache::forget('all_permissions_list');
        Cache::forget('all_roles_list');
        // activity()
        //     ->performedOn($permission) // O objeto que sofreu a ação
        //     ->causedBy(auth()->user()) // O usuário que causou a ação
        //     ->log("A permissão foi alterada");

        return redirect()->route('system.permissions.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        if ($this->isProtected($permission)) {
            abort(403, 'Esta permissão é protegida e não pode ser excluída.');
        }
        // Usamos a autorização do FormRequest ou uma Policy para verificar a permissão
        // Para simplicidade aqui, vamos verificar diretamente.
        if (Auth::user()->cannot('delete permissions')) {
            abort(403);
        }

        $permission->delete();

        return redirect()->route('system.permissions.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    public function selection(Request $request)
    {
        $searchTerm = $request->input('search', '');

        $permissions = Permission::where('name', 'LIKE', '%' . $searchTerm . '%')
            ->orderBy('name')
            ->paginate(10);

        return response()->json($permissions);
    }
}
