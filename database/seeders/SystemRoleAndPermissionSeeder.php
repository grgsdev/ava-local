<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Level;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class SystemRoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa o cache de permissões para evitar erros
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        $systemEngineerRole = Role::firstOrCreate(['name' => 'System Engineering']);

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
    }
}
