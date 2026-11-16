<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserCompanyRoleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function um_utilizador_pode_ter_um_perfil_atribuido_dentro_de_uma_empresa(): void
    {
        // 1. Arrange: Prepara o cenário
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $role = 'Editor';

        // 2. Act: Executa a ação
        $user->companies()->attach($company, ['role' => $role]);

        // 3. Assert: Verifica o resultado
        $this->assertDatabaseHas('company_user', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => $role,
        ]);

        // Verifica se o relacionamento está a funcionar
        $this->assertEquals($role, $user->companies()->first()->pivot->role);
    }
}
