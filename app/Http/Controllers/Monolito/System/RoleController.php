<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\System\Role;
use Illuminate\Http\Request;
use App\Services\QueryService;
use App\Models\System\Permission;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\BaseController;
use App\Http\Resources\System\RoleResource;
use App\Http\Requests\System\StoreRoleRequest;
use App\Http\Requests\System\UpdateRoleRequest;
use App\Http\Resources\System\PermissionResource;

class RoleController extends BaseController
{
    /**
     * Verifica se um papel é protegido pela configuração.
     *
     * @param Role $role
     * @return bool
     */
    private function isProtected(Role $role): bool
    {
        return in_array($role->name, config('roles.protected', []));
    }

    public function index(Request $request): Response
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '')
            ->add(__('Roles'), '');

        $protectedRoles = config('roles.protected', []);
        $roles = QueryService::query(
            Role::class,
            Role::ALLOWEDFILTERS(),
            ['permissions'],
            Role::ALLOWEDSORTS(),
        ) // Exclui os papéis protegidos da consulta
            ->whereNotIn('name', $protectedRoles)
            ->withCount('permissions')
            ->paginate($request->get('per_page', 15))->withQueryString();


        return Inertia::render('System/Roles/Index', [
            'roles' => RoleResource::collection($roles),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
        ]);
    }

    /**
     * Mostra o formulário para criar um novo papel.
     */
    public function create(): Response
    {
        $protectedPermissions = config('permissions.protected', []);
        $permissions = Cache::remember('all_permissions_list', now()->addHour(), function () use ($protectedPermissions) {
            return Permission::query()->whereNotIn('name', $protectedPermissions)->select('id', 'name')->orderBy('name')->get();
        });

        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Roles'), route('system.roles.index'))->add(__('Add'), '');

        return Inertia::render('System/Roles/Create', [
            'permissions' => PermissionResource::collection($permissions)
        ]);
    }

    /**
     * Salva o novo papel e sincroniza suas permissões.
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create(['name' => $request->name]);

        // O método syncPermissions do Spatie é perfeito para isso
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Limpamos o cache da lista de permissões, pois uma associação pode ter mudado
        Cache::forget('all_permissions_list');
        Cache::forget('all_roles_list');

        return redirect()->route('system.roles.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    public function edit(Role $role): Response
    {
        if ($this->isProtected($role)) {
            abort(403, 'Este papel é protegido e não pode ser editado.');
        }
        $protectedPermissions = config('permissions.protected', []);

        // Carrega as permissões que JÁ ESTÃO associadas a este papel
        $role->load('permissions:id,name');
        $allPermissions = Cache::remember('all_permissions_list', now()->addHour(), function () use ($protectedPermissions) {
            return Permission::query()->whereNotIn('name', $protectedPermissions)->select('id', 'name')->orderBy('name')->get();
        });

        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '') // Link nulo para item não clicável
            ->add(__('Roles'), route('system.roles.index'))->add(__('Edit') . ' ' . $role->name, '');

        return Inertia::render('System/Roles/Edit', [
            'role' => new RoleResource($role),
            // Passamos a lista completa de permissões como uma nova prop
            'allPermissions' => PermissionResource::collection($allPermissions),
        ]);
    }
    /**
     * Atualiza o papel no banco de dados.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        if ($this->isProtected($role)) {
            abort(403, 'Este papel é protegido e não pode ser atualizado.');
        }

        $role->update(['name' => $request->name]);

        // Sincroniza as permissões. Se o array 'permissions' não for enviado,
        // ele remove todas as permissões do papel.
        $role->syncPermissions($request->input('permissions', []));
        Cache::forget('all_permissions_list');
        Cache::forget('all_roles_list');
        return redirect()->route('system.roles.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }

    /**
     * Remove o papel especificado do banco de dados.
     */
    public function destroy(Role $role)
    {
        if ($this->isProtected($role)) {
            abort(403, 'Este papel é protegido e não pode ser excluído.');
        }

        // Verifica se o usuário tem a permissão para apagar papéis
        if (Auth::user()->cannot('delete roles')) {
            abort(403, 'Ação não autorizada.');
        }

        $role->delete();

        return redirect()->route('system.roles.index')
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

        $roles = Role::where('name', 'LIKE', '%' . $searchTerm . '%')
            ->orderBy('name')
            ->paginate(10);

        return response()->json($roles);
    }
}
