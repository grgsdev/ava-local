<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardCompanyContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function an_editor_with_one_active_membership_reaches_the_dashboard_with_company_context(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('active_company_id', $company->id);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard/Index')
                ->where('active_company.id', $company->id)
                ->where('active_company.name', $company->name)
                ->where('auth.company_can', fn ($can) => $can->contains('edit projects') && ! $can->contains('approve projects')));
    }

    #[Test]
    public function an_adm_membership_receives_approval_capability_on_the_dashboard(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'ADM Cliente PJ');

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSessionHas('active_company_id', $company->id)
            ->assertInertia(fn ($page) => $page
                ->where('active_company.id', $company->id)
                ->where('auth.company_can', fn ($can) => $can->contains('approve projects')));
    }

    #[Test]
    public function a_master_without_a_company_membership_can_access_both_dashboard_routes(): void
    {
        $master = User::factory()->create();
        $master->assignRole('Master');

        $this->actingAs($master)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard/Index')
                ->where('active_company', null)
                ->where('auth.company_can', []));
    }

    #[Test]
    public function a_master_without_a_company_membership_is_forbidden_from_the_company_team(): void
    {
        $master = User::factory()->create();
        $master->assignRole('Master');

        $this->actingAs($master)
            ->get('/team')
            ->assertForbidden();
    }

    #[Test]
    public function an_adm_can_access_the_team_for_the_active_company(): void
    {
        $company = Company::factory()->create();
        $adm = $this->userInCompany($company, 'ADM Cliente PJ');

        $this->actingAs($adm)
            ->get('/team')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Team/Index')
                ->where('active_company.id', $company->id));
    }

    #[Test]
    public function an_editor_remains_forbidden_from_team_management(): void
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');

        $this->actingAs($editor)
            ->get('/team')
            ->assertForbidden();
    }

    #[Test]
    public function multiple_active_memberships_require_existing_company_selection(): void
    {
        $user = User::factory()->create();
        $user->companies()->attach([
            Company::factory()->create()->id => ['role' => 'Editor', 'status' => 'active'],
            Company::factory()->create()->id => ['role' => 'Editor', 'status' => 'active'],
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('companies.select'))
            ->assertSessionMissing('active_company_id');
    }

    #[Test]
    public function invalid_or_inactive_company_selection_is_cleared_and_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');
        $user->companies()->updateExistingPivot($company->id, ['status' => 'inactive']);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get('/dashboard')
            ->assertForbidden()
            ->assertSessionMissing('active_company_id');
    }

    #[Test]
    public function a_company_outside_the_users_memberships_cannot_become_the_active_context(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');

        $this->actingAs($user)
            ->withSession(['active_company_id' => $otherCompany->id])
            ->get('/dashboard')
            ->assertRedirect(route('companies.select'))
            ->assertSessionMissing('active_company_id');
    }

    #[Test]
    public function a_user_without_an_active_membership_fails_closed_on_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => $role, 'status' => 'active']);

        return $user;
    }
}
