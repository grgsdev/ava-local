<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function a_user_can_see_only_projects_from_their_own_company(): void
    {
        $companyA = Company::factory()->create();
        $userA = User::factory()->create();
        $userA->companies()->attach($companyA, ['role' => 'Editor']);

        $companyB = Company::factory()->create();

        $projectA = Project::factory()->create(['title' => 'Projeto Visível', 'company_id' => $companyA->id, 'user_id' => $userA->id]);
        $projectB = Project::factory()->create(['title' => 'Projeto Invisível', 'company_id' => $companyB->id, 'user_id' => $userA->id]);

        $this->app->singleton('current_company', fn() => $companyA);

        $this->actingAs($userA)
            ->get('/projects')
            ->assertOk()
            // --- SUBSTITUA AS VERIFICAÇÕES ANTIGAS POR ESTA ---
            ->assertInertia(
                fn(Assert $page) => $page
                    ->component('Projects/Index') // Verifica se o componente correto foi renderizado
                    ->has('projects.data', 1) // Verifica se há exatamente 1 projeto nos props
                    ->where('projects.data.0.title', $projectA->title) // Verifica se o título do único projeto é o correto
            );
    }

    #[Test]
    public function an_authenticated_user_can_access_the_project_creation_page(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $this->app->singleton('current_company', fn() => $company);

        $this->actingAs($user)
            ->get('/projects/create')
            ->assertOk();
    }

    #[Test]
    public function an_authenticated_user_can_create_a_project(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $this->app->singleton('current_company', fn() => $company);

        $projectData = [
            'title' => 'Meu Novo Projeto de Teste',
            'type' => 'single_video',
        ];

        $this->actingAs($user)
            ->post('/projects', $projectData)
            ->assertRedirect('/projects');

        $this->assertDatabaseHas('projects', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'title' => 'Meu Novo Projeto de Teste',
        ]);
    }

    #[Test]
    public function a_user_cannot_edit_a_project_from_another_company(): void
    {
        // 1. Arrange: Cria duas empresas, um utilizador na empresa A e um projeto na empresa B
        $companyA = Company::factory()->create();
        $userA = User::factory()->create();
        $userA->companies()->attach($companyA, ['role' => 'Editor']);

        $companyB = Company::factory()->create();
        $projectB = Project::factory()->create(['company_id' => $companyB->id, 'user_id' => User::factory()->create()->id]);

        // Define a empresa A como a atual
        $this->app->singleton('current_company', fn() => $companyA);

        // 2. Act & Assert: Tenta aceder à página de edição do projeto B e espera ser barrado
        $this->actingAs($userA)
            ->get('/projects/' . $projectB->id . '/edit')
            ->assertForbidden();
    }

    #[Test]
    public function an_authorized_user_can_update_a_project(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

        $this->app->singleton('current_company', fn() => $company);

        $updatedData = [
            'title' => 'Título do Projeto Atualizado',
            'type' => 'flow_video',
        ];

        $this->actingAs($user)
            ->put('/projects/' . $project->id, $updatedData)
            ->assertRedirect('/projects');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Título do Projeto Atualizado',
            'type' => 'flow_video',
        ]);
    }

    #[Test]
    public function a_user_cannot_delete_a_project_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $userA = User::factory()->create();
        $userA->companies()->attach($companyA, ['role' => 'Editor']);

        $companyB = Company::factory()->create();
        $projectB = Project::factory()->create(['company_id' => $companyB->id, 'user_id' => User::factory()->create()->id]);

        $this->app->singleton('current_company', fn() => $companyA);

        // Tenta apagar o projeto da empresa B e espera ser barrado
        $this->actingAs($userA)
            ->delete('/projects/' . $projectB->id)
            ->assertForbidden();
    }

    #[Test]
    public function an_authorized_user_can_delete_a_project(): void
    {
        // Marca o teste como incompleto, explicando a razão.
        // Ele não será executado, mas ficará documentado na nossa suíte de testes.
        $this->markTestIncomplete(
            'Este teste foi desativado e será reativado quando a funcionalidade de "hard delete" (exclusão permanente) for implementada.'
        );

        // O código original do teste permanece aqui, mas não será executado.
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->delete('/projects/' . $project->id)
            ->assertRedirect('/projects');

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    #[Test]
    public function an_authorized_user_can_soft_delete_a_project(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Manager']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

        $this->app->singleton('current_company', fn() => $company);

        // Act
        $this->actingAs($user)
            ->delete('/projects/' . $project->id);

        // Assert
        // Verifica se o projeto foi "marcado como excluído" na base de dados
        $this->assertSoftDeleted($project);
    }

    #[Test]
    public function an_authorized_user_can_restore_a_soft_deleted_project(): void
    {
        // Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $project->delete(); // Inativa o projeto primeiro

        $this->app->singleton('current_company', fn() => $company);

        // Act
        $this->actingAs($user)
            ->post('/projects/' . $project->id . '/restore') // Rota que vamos criar
            ->assertRedirect(); // Espera um redirecionamento

        // Assert
        // Verifica se o projeto NÃO está mais "marcado como excluído"
        $this->assertNotSoftDeleted($project);
    }
}
