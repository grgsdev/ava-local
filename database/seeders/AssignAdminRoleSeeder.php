<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignAdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Defina o email do seu utilizador principal
        $userEmail = 'admclient@app.com'; // <-- SUBSTITUA PELO SEU EMAIL

        // 2. Encontre o seu utilizador no banco de dados
        $user = User::where('email', $userEmail)->first();

        // 3. Encontre ou crie uma empresa padrão para o MVP
        $company = Company::firstOrCreate(
            ['name' => 'Empresa Padrão MVP'],
            ['balance' => 0]
        );

        // 4. Se o utilizador e a empresa existirem, ligue-os com o perfil correto
        if ($user && $company) {
            $this->command->info("A ligar o utilizador {$user->name} à empresa {$company->name} com o perfil 'ADM Cliente PJ'...");

            // Usa syncWithoutDetaching para garantir que a ligação seja criada
            // sem duplicar, caso o seeder seja executado mais de uma vez.
            $user->companies()->syncWithoutDetaching([
                $company->id => ['role' => 'ADM Cliente PJ']
            ]);

            $this->command->info('Ligação realizada com sucesso!');
        } else {
            $this->command->error("Utilizador com o e-mail {$userEmail} não encontrado. Por favor, verifique o e-mail no seeder.");
        }
    }
}
