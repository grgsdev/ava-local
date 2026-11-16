<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Role::firstOrCreate(['name' => 'No Project Access']);
    }

    #[Test]
    public function an_editor_can_submit_an_editable_project_and_the_transition_is_logged(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');
        $project = Project::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => Project::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('projects.submit', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'company_id' => $company->id,
            'status' => Project::STATUS_PENDING_APPROVAL,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'project_status',
            'event' => 'submitted',
            'subject_type' => Project::class,
            'subject_id' => $project->id,
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'description' => 'Project enviado para aprovação',
        ]);

        $activity = Activity::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(Project::STATUS_DRAFT, $activity->properties->get('from'));
        $this->assertSame(Project::STATUS_PENDING_APPROVAL, $activity->properties->get('to'));
        $this->assertSame($company->id, $activity->properties->get('company_id'));
    }

    #[Test]
    public function a_project_from_another_company_cannot_be_submitted(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA, 'Editor');
        $project = Project::factory()->create(['company_id' => $companyB->id, 'user_id' => $user->id, 'status' => Project::STATUS_DRAFT]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->post(route('projects.submit', $project))
            ->assertForbidden();
    }

    #[Test]
    public function a_project_from_another_company_cannot_be_opened_for_submission_confirmation(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA, 'Editor');
        $project = Project::factory()->create(['company_id' => $companyB->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('projects.submit.confirmation', $project))
            ->assertForbidden();
    }

    #[Test]
    public function a_user_without_active_membership_cannot_submit(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'status' => Project::STATUS_DRAFT]);

        $this->actingAs($user)
            ->post(route('projects.submit', $project))
            ->assertForbidden();
    }

    #[Test]
    public function a_user_without_edit_permission_cannot_submit(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'No Project Access');
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'status' => Project::STATUS_DRAFT]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('projects.submit', $project))
            ->assertForbidden();
    }

    #[Test]
    public function a_project_can_only_be_submitted_once_from_editable_state(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'status' => Project::STATUS_PENDING_APPROVAL]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('projects.submit', $project))
            ->assertStatus(422);
    }

    #[Test]
    public function a_non_editable_project_state_is_rejected(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'status' => 'Aprovado']);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('projects.submit', $project))
            ->assertStatus(422);
    }

    #[Test]
    public function an_adm_can_see_only_pending_projects_from_the_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA, 'ADM Cliente PJ');
        $visible = Project::factory()->create(['company_id' => $companyA->id, 'user_id' => $user->id, 'status' => Project::STATUS_PENDING_APPROVAL]);
        Project::factory()->create(['company_id' => $companyA->id, 'user_id' => $user->id, 'status' => Project::STATUS_DRAFT]);
        Project::factory()->create(['company_id' => $companyB->id, 'user_id' => $user->id, 'status' => Project::STATUS_PENDING_APPROVAL]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/Pending')
                ->has('projects.data', 1)
                ->where('projects.data.0.id', $visible->id));
    }

    #[Test]
    public function an_editor_can_open_the_submit_index_for_existing_projects(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');
        $project = Project::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'status' => Project::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('projects.submit.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Projects/SubmitIndex')
                ->has('projects.data', 1)
                ->where('projects.data.0.id', $project->id));
    }

    #[Test]
    public function a_user_without_approval_permission_cannot_access_the_pending_queue(): void
    {
        $company = Company::factory()->create();
        $user = $this->userInCompany($company, 'Editor');

        $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('projects.pending'))
            ->assertForbidden();
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => $role, 'status' => 'active']);

        return $user;
    }
}
