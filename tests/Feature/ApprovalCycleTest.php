<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ApprovalCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function an_adm_rejects_a_pending_workflow_with_persistent_feedback_and_an_editor_resubmits_the_same_project(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->workflow();

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('projects.data.0.id', $project->id)
                ->where('projects.data.0.status', Project::STATUS_PENDING_APPROVAL));

        $this->post(route('projects.reject', $project), ['feedback' => 'Ajuste a introdução.'])
            ->assertRedirect();

        $project->refresh();
        $this->assertSame(Project::STATUS_REJECTED, $project->status);
        $rejection = Activity::query()->where('subject_id', $project->id)->where('event', 'rejected')->firstOrFail();
        $this->assertSame($adm->id, $rejection->causer_id);
        $this->assertSame('Ajuste a introdução.', $rejection->properties->get('feedback'));
        $this->assertNotNull($rejection->created_at);

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects.data', 0));

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('courses.data.0.project.status', Project::STATUS_REJECTED)
                ->where('courses.data.0.project.rejection_feedback', 'Ajuste a introdução.'));

        $this->put(route('admin.courses.update', $course), [
            'title' => 'Course corrigido',
            'price' => 0,
            'level' => 'beginner',
        ])->assertRedirect(route('admin.courses.index'));

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))
            ->assertRedirect(route('admin.courses.index'));

        $project->refresh();
        $this->assertSame(Project::STATUS_PENDING_APPROVAL, $project->status);
        $this->assertSame(1, Project::query()->where('course_id', $course->id)->count());
        $this->assertDatabaseHas('activity_log', ['subject_id' => $project->id, 'event' => 'rejected']);
        $this->assertDatabaseHas('activity_log', ['subject_id' => $project->id, 'event' => 'submitted']);
    }

    #[Test]
    public function an_editor_cannot_edit_a_pending_course_but_an_adm_can_review_it(): void
    {
        [$company, $editor, $adm, $course] = $this->workflow();

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->put(route('admin.courses.update', $course), [
                'title' => 'Alteração bloqueada',
                'price' => 0,
                'level' => 'beginner',
            ])->assertForbidden();

        $this->get(route('admin.courses.builder.index', $course))->assertForbidden();
        $this->post(route('admin.courses.builder.modules.store', $course), ['title' => 'Bloqueado'])
            ->assertForbidden();
        $this->assertSame(0, CourseModule::query()->where('course_id', $course->id)->count());

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->get(route('admin.courses.edit', $course))
            ->assertOk();
        $this->get(route('admin.courses.builder.index', $course))->assertOk();
    }

    #[Test]
    public function rejection_requires_feedback_and_editors_or_other_companies_cannot_decide(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->workflow();
        $otherCompany = Company::factory()->create();
        $otherAdm = $this->userInCompany($otherCompany, 'ADM Cliente PJ');

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.reject', $project), ['feedback' => '   '])
            ->assertSessionHasErrors('feedback');

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.reject', $project), ['feedback' => 'No'])
            ->assertForbidden();
        $this->post(route('projects.approve', $project))->assertForbidden();

        $this->actingAs($otherAdm)->withSession(['active_company_id' => $otherCompany->id])
            ->post(route('projects.reject', $project), ['feedback' => 'No'])
            ->assertForbidden();
        $this->post(route('projects.approve', $project))->assertForbidden();
    }

    #[Test]
    public function an_adm_approves_only_a_pending_project_and_approval_does_not_publish_the_course(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->workflow();

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.approve', $project))
            ->assertRedirect();

        $project->refresh();
        $this->assertSame(Project::STATUS_APPROVED, $project->status);
        $this->assertFalse($course->fresh()->is_published);
        $activity = Activity::query()->where('subject_id', $project->id)->where('event', 'approved')->firstOrFail();
        $this->assertSame($adm->id, $activity->causer_id);
        $this->assertNotNull($activity->created_at);

        $this->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))
            ->assertStatus(422);

        $this->post(route('projects.reject', $project), ['feedback' => 'Tarde demais.'])
            ->assertStatus(422);
    }

    #[Test]
    public function a_master_can_approve_a_pending_project_without_a_company_membership(): void
    {
        [, , , , $project] = $this->workflow();
        $master = User::factory()->create();
        $master->assignRole('Master');

        $this->actingAs($master)
            ->post(route('projects.approve', $project))
            ->assertRedirect();

        $this->assertSame(Project::STATUS_APPROVED, $project->fresh()->status);
    }

    private function workflow(): array
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $adm = $this->userInCompany($company, 'ADM Cliente PJ');
        $course = Course::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'title' => 'Course '.uniqid(),
            'slug' => 'course-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
        $project = Project::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'course_id' => $course->id,
            'title' => $course->title,
            'type' => 'course',
            'status' => Project::STATUS_PENDING_APPROVAL,
        ]);

        return [$company, $editor, $adm, $course, $project];
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => $role, 'status' => 'active']);

        return $user;
    }
}
