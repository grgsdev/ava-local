<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class); // <-- EXECUTE O SEEDER ANTES DE CADA TESTE
    }
    #[Test]
    public function an_unauthorized_user_cannot_access_the_team_management_page(): void
    {
        // 1. Arrange: Cria uma empresa e um utilizador com o perfil 'Editor'
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        // 2. Act & Assert: Tenta aceder à página como este utilizador
        // e verifica se o acesso é proibido (403)
        $this->actingAs($user)
            ->get('/team') // Usaremos a rota /team para a gestão de equipa
            ->assertForbidden();
    }

    #[Test]
    public function an_authorized_user_can_access_the_team_management_page(): void
    {
        // 1. Arrange: Cria uma empresa e um utilizador com o perfil 'ADM Cliente PJ'
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'ADM Cliente PJ']);

        // 2. Act & Assert: Tenta aceder à página como este utilizador
        // e verifica se o acesso é permitido (200)
        $this->actingAs($user)
            ->get('/team')
            ->assertOk();
    }
}
