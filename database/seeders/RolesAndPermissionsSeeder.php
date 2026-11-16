<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa o cache de roles e permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $permissions = [
            'manage settings',
            'view settings',
            'edit settings',

            'manage users',
            'active users',
            'inactive users',
            'approve users',
            'unapprove users',
            'view users',
            'create users',
            'edit users',
            'delete users',
            'force-delete users',
            'restore users',
            'manage notifications',
            'active notifications',
            'inactive notifications',
            'approve notifications',
            'unapprove notifications',
            'view notifications',
            'create notifications',
            'edit notifications',
            'delete notifications',
            'force-delete notifications',
            'restore notifications',
            'manage system',
            'manage permissions',
            'view permissions',
            'create permissions',
            'update permissions',
            'delete permissions',
            'manage roles',
            'view roles',
            'create roles',
            'update roles',
            'delete roles',
            'manage logs',
            'view logs',
            'delete logs',
            'manage users logs',
            'view users logs',
            'delete users logs',
            'manage command logs',
            'view command logs',
            'delete command logs',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        // --- Crie as Permissões ---
        // Permissão para gerir a equipa (criar/editar Managers e Editors)
        Permission::firstOrCreate(['name' => 'manage team']);

        // Permissões de Projeto
        Permission::firstOrCreate(['name' => 'manage projects']);

        Permission::firstOrCreate(['name' => 'create projects']);
        Permission::firstOrCreate(['name' => 'edit projects']);
        Permission::firstOrCreate(['name' => 'delete projects']);
        Permission::firstOrCreate(['name' => 'approve projects']);


        // --- Crie os Perfis (Roles) e Atribua as Permissões ---

        // Perfil Editor: Pode criar e editar os seus próprios projetos.
        Role::firstOrCreate(['name' => 'Editor'])
            ->givePermissionTo(['manage projects', 'create projects', 'edit projects']);

        // Perfil Manager: Pode fazer tudo que o Editor faz, e também apagar, aprovar e gerir a equipa.
        Role::firstOrCreate(['name' => 'Manager'])
            ->syncPermissions(['manage projects', 'create projects', 'edit projects', 'delete projects', 'approve projects', 'manage team']);

        // Perfil ADM Cliente PJ: Tem as mesmas permissões do Manager (pode ser expandido no futuro).
        $admClientePJ = Role::firstOrCreate(['name' => 'ADM Cliente PJ'])
            ->syncPermissions(['manage projects', 'create projects', 'edit projects', 'delete projects', 'approve projects', 'manage team']);

        // Perfil Master: Não precisa de permissões explícitas aqui, pois o Gate dará acesso total.
        $masterRole = Role::firstOrCreate(['name' => 'Master']);
        $masterRole->givePermissionTo(Permission::all());
        $adminUser = User::firstOrCreate(
            ['email' => 'master@app.com'],
            [
                'name' => 'Master Sistema',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                // Adiciona o campo is_active
            ]
        );
        $adminUser->syncRoles([$masterRole]);

        $systemEngineerRole = Role::firstOrCreate(['name' => 'System Engineering']);
        $systemEngineerRole->givePermissionTo(Permission::all());
        $engineerUser = User::firstOrCreate(
            ['email' => 'engineer@app.com'],
            [
                'name' => 'System Engineer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),

                'is_active' => true,

            ]
        );
        $engineerUser->syncRoles($systemEngineerRole);

        $testerUser = User::firstOrCreate(
            ['email' => 'admclient@app.com'],
            [
                'name' => 'Adm Cliente PJ',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),

                'is_active' => true,

            ]
        );
        $testerUser->syncRoles($admClientePJ);
    }
}
