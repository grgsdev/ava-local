<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Level;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpa o cache de permissões e papéis para garantir que as novas sejam aplicadas
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // --- 1. CRIAR AS PERMISSÕES ---
        // Vamos criar um conjunto de permissões para cada funcionalidade do novo módulo.
        $permissions = [
            'access system panel',
            'view system_module',

            'view system_developers module',
            'view system_developers',
            'create system_developers',
            'update system_developers',
            'delete system_developers',

            'manage permissions', // Permissão geral para ver o módulo de engenharia
            'view permissions',
            'create permissions',
            'update permissions',
            'delete permissions',

            'manage roles', // Permissão geral para ver o módulo de engenharia
            'view roles',
            'create roles',
            'update roles',
            'delete roles',

            'manage logs', // Permissão geral para ver o módulo de engenharia
            'view logs',
            'delete logs',

            'manage command logs', // Permissão geral para ver o módulo de engenharia
            'view command logs',

            'delete command logs',

            'view generator module', // Permissão geral para ver o módulo de engenharia
            'view generator',
            'create generator',
            'update generator',
            'delete generator',
            'run generator',


        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- 2. CRIAR OS PAPÉIS (ROLES) ---

        // O papel de "Super Admin" que terá todas as permissões do módulo.
        $systemEngineerRole = Role::firstOrCreate(['name' => 'System Engineering']);
        $systemDeveloperRole = Role::firstOrCreate(['name' => 'System Developer']);

        $systemEngineerRole->givePermissionTo(Permission::all()); // Dando todas as permissões por enquanto.

        $systemDeveloperRole->givePermissionTo(Permission::all()); // Damos a permissão de acesso

        $panelUserRole = Role::firstOrCreate(['name' => 'Panel User']);
        $panelUserRole->givePermissionTo('access panel');



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

        $developerUser = User::firstOrCreate(
            ['email' => 'developer@app.com'],
            [
                'name' => 'System Developer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),

                'is_active' => true,

            ]
        );

        // Atribui o papel de System Engineering a este usuário.
        $developerUser->syncRoles($systemDeveloperRole);
    }
}
