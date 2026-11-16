<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectAuthorizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'No Project Access']);
    }

    #[Test]
    public function a_user_without_active_memberships_fails_closed(): void
    {
        $this->actingAs(User::factory()->create())->get('/projects')->assertForbidden();
    }

    #[Test]
    public function one_active_membership_is_selected_automatically(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company);

        $this->actingAs($user)
            ->get('/projects')
            ->assertOk()
            ->assertSessionHas('active_company_id', $company->id);
    }

    #[Test]
    public function multiple_active_memberships_require_explicit_selection(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach([
            $companyA->id => ['role' => 'Editor', 'status' => 'active'],
            $companyB->id => ['role' => 'Editor', 'status' => 'active'],
        ]);

        $this->actingAs($user)
            ->get('/projects')
            ->assertRedirect(route('companies.select'));
    }

    #[Test]
    public function selection_accepts_only_an_active_membership(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($companyA, ['role' => 'Editor', 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('companies.select.store'), ['company_id' => $companyB->id])
            ->assertForbidden();

        $this->post(route('companies.select.store'), ['company_id' => $companyA->id])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('active_company_id', $companyA->id);
    }

    #[Test]
    public function an_inactive_selected_membership_invalidates_the_context(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach([
            $companyA->id => ['role' => 'Editor', 'status' => 'active'],
            $companyB->id => ['role' => 'Editor', 'status' => 'active'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get('/projects')
            ->assertOk();

        $user->companies()->updateExistingPivot($companyA->id, ['status' => 'inactive']);

        $this->withSession(['active_company_id' => $companyA->id])
            ->get('/projects')
            ->assertRedirect(route('companies.select'));
    }

    #[Test]
    public function project_access_and_update_are_limited_to_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA);
        $projectB = Project::factory()->create(['company_id' => $companyB->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get("/projects/{$projectB->id}/edit")
            ->assertForbidden();

        $this->withSession(['active_company_id' => $companyA->id])
            ->put("/projects/{$projectB->id}", ['title' => 'Cross company', 'type' => 'single_video'])
            ->assertForbidden();
    }

    #[Test]
    public function project_creation_uses_only_the_validated_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->post('/projects', [
                'title' => 'Projeto seguro',
                'type' => 'single_video',
                'company_id' => $companyB->id,
            ])
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'title' => 'Projeto seguro',
            'company_id' => $companyA->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('projects', [
            'title' => 'Projeto seguro',
            'company_id' => $companyB->id,
        ]);
    }

    #[Test]
    public function a_membership_without_project_permission_is_denied(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'No Project Access', 'status' => 'active']);

        $this->actingAs($user)->get('/projects')->assertForbidden();
    }

    #[Test]
    public function company_capabilities_follow_the_active_membership_in_the_inertia_props(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'tenant'])
            ->get('/__testing/company-capabilities', fn () => Inertia::render('Projects/SubmitIndex', ['projects' => []]));

        $editorCompany = Company::factory()->create();
        $noAccessCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach([
            $editorCompany->id => ['role' => 'Editor', 'status' => 'active'],
            $noAccessCompany->id => ['role' => 'No Project Access', 'status' => 'active'],
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $editorCompany->id])
            ->get('/__testing/company-capabilities')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.can', [])
                ->where('auth.company_can', fn ($can) => $can->contains('edit projects') && ! $can->contains('approve projects')));

        $this->withSession(['active_company_id' => $noAccessCompany->id])
            ->get('/__testing/company-capabilities')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.company_can', fn ($can) => ! $can->contains('edit projects') && ! $can->contains('approve projects')));
    }

    #[Test]
    public function an_adm_membership_receives_approval_capability_in_the_inertia_props(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'tenant'])
            ->get('/__testing/company-capabilities', fn () => Inertia::render('Projects/SubmitIndex', ['projects' => []]));

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'ADM Cliente PJ', 'status' => 'active']);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get('/__testing/company-capabilities')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.company_can', fn ($can) => $can->contains('approve projects')));
    }

    private function userInCompany(Company $company): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor', 'status' => 'active']);

        return $user;
    }
}
