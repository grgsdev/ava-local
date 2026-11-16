<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function a_team_administrator_can_invite_a_new_team_member(): void
    {
        // 1. Arrange: Cria uma empresa e um utilizador administrador
        $company = Company::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->companies()->attach($company, ['role' => 'ADM Cliente PJ']);

        // 2. Act: Simula o envio do formulário de convite
        $response = $this->actingAs($adminUser)->post('/team/invitations', [
            'name' => 'Novo Membro',
            'email' => 'novo@membro.com',
            'role' => 'Editor',
        ]);

        // 3. Assert: Verifica os resultados
        $response->assertRedirect('/team');
        $this->assertDatabaseHas('users', ['email' => 'novo@membro.com']);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => User::where('email', 'novo@membro.com')->first()->id,
            'role' => 'Editor',
        ]);
    }

    #[Test]
    public function a_team_administrator_can_update_a_team_member_role(): void
    {
        // 1. Arrange: Cria um admin e um membro da equipa com o perfil 'Editor'
        $company = Company::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->companies()->attach($company, ['role' => 'ADM Cliente PJ']);

        $memberToUpdate = User::factory()->create();
        $memberToUpdate->companies()->attach($company, ['role' => 'Editor']);

        // 2. Act: Simula o envio do formulário de atualização para mudar o perfil para 'Manager'
        $response = $this->actingAs($adminUser)->put('/team/members/' . $memberToUpdate->id, [
            'role' => 'Manager',
        ]);

        // 3. Assert: Verifica se a alteração foi bem-sucedida
        $response->assertRedirect('/team');
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $memberToUpdate->id,
            'role' => 'Manager', // O perfil foi atualizado
        ]);
        $this->assertDatabaseMissing('company_user', [
            'company_id' => $company->id,
            'user_id' => $memberToUpdate->id,
            'role' => 'Editor', // O perfil antigo já não existe
        ]);
    }

    #[Test]
    public function a_team_administrator_can_remove_a_team_member(): void
    {
        // 1. Arrange: Cria um admin e um membro
        $company = Company::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->companies()->attach($company, ['role' => 'ADM Cliente PJ']);

        $memberToRemove = User::factory()->create();
        $memberToRemove->companies()->attach($company, ['role' => 'Editor']);

        // Garante que o registo existe antes da ação
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $memberToRemove->id,
        ]);

        // 2. Act: Simula a requisição para remover o membro
        $response = $this->actingAs($adminUser)->delete('/team/members/' . $memberToRemove->id);

        // 3. Assert: Verifica se a remoção foi bem-sucedida
        $response->assertRedirect('/team');
        $this->assertDatabaseMissing('company_user', [ // O vínculo na tabela-pivô foi removido
            'company_id' => $company->id,
            'user_id' => $memberToRemove->id,
        ]);
        $this->assertDatabaseHas('users', ['id' => $memberToRemove->id]); // O utilizador ainda existe na tabela 'users'
    }
}
