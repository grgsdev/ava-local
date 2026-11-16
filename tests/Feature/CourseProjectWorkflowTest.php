<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourseProjectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function the_project_course_column_is_nullable_unique_and_restricts_invalid_or_duplicate_links(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $course = $this->course($company, $owner);

        $this->assertTrue(Schema::hasColumn('projects', 'course_id'));

        try {
            Project::create([
                'company_id' => $company->id,
                'user_id' => $owner->id,
                'course_id' => 999999,
                'title' => 'Invalid workflow',
                'type' => 'course',
            ]);
            $this->fail('An unknown course ID must be rejected by the foreign key.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        Project::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'title' => 'Workflow Course',
            'type' => 'course',
        ]);

        $this->expectException(QueryException::class);

        Project::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'title' => 'Duplicate workflow',
            'type' => 'course',
        ]);
    }

    #[Test]
    public function an_editor_submits_an_authorized_course_into_an_idempotent_company_workflow(): void
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $course = $this->course($company, $editor);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('projects', [
            'course_id' => $course->id,
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'status' => Project::STATUS_PENDING_APPROVAL,
        ]);

        $this->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSame(1, Project::query()->where('course_id', $course->id)->count());
    }

    #[Test]
    public function an_editor_cannot_submit_a_course_from_another_company_or_a_legacy_course(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editor = $this->userInCompany($companyA, 'Editor');
        $courseB = $this->course($companyB, User::factory()->create());
        $legacyCourse = $this->course(null, $editor);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $companyA->id])
            ->post(route('admin.courses.submit', $courseB))
            ->assertForbidden();

        $this->withSession(['active_company_id' => $companyA->id])
            ->post(route('admin.courses.submit', $legacyCourse))
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', ['course_id' => $courseB->id]);
        $this->assertDatabaseMissing('projects', ['course_id' => $legacyCourse->id]);
    }

    #[Test]
    public function the_adm_pending_queue_identifies_and_opens_only_its_company_course_workflow(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editor = $this->userInCompany($companyA, 'Editor');
        $admA = $this->userInCompany($companyA, 'ADM Cliente PJ');
        $admB = $this->userInCompany($companyB, 'ADM Cliente PJ');
        $course = $this->course($companyA, $editor);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $companyA->id])
            ->post(route('admin.courses.submit', $course))
            ->assertRedirect();

        $workflow = Project::query()->where('course_id', $course->id)->firstOrFail();
        $this->actingAs($admA)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('projects.data', 1)
                ->where('projects.data.0.course.id', $course->id));

        $this->withSession(['active_company_id' => $companyA->id])
            ->get(route('admin.courses.edit', $course))
            ->assertOk();

        Project::factory()->create([
            'company_id' => $companyA->id,
            'user_id' => $editor->id,
            'status' => Project::STATUS_PENDING_APPROVAL,
            'course_id' => null,
        ]);

        $this->withSession(['active_company_id' => $companyA->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects.data', 2));

        $this->actingAs($admB)
            ->withSession(['active_company_id' => $companyB->id])
            ->get(route('projects.pending'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('projects.data', 0));

        $this->withSession(['active_company_id' => $companyB->id])
            ->get(route('admin.courses.edit', $course))
            ->assertForbidden();

        $this->assertSame($companyA->id, $workflow->company_id);
    }

    #[Test]
    public function a_course_referenced_by_a_workflow_cannot_be_physically_deleted(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $course = $this->course($company, $owner);

        Project::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'title' => 'Workflow Course',
            'type' => 'course',
        ]);

        $this->expectException(QueryException::class);
        DB::table('courses')->whereKey($course->id)->delete();
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function course(?Company $company, User $owner): Course
    {
        return Course::create([
            'company_id' => $company?->id,
            'user_id' => $owner->id,
            'title' => 'Course '.uniqid(),
            'slug' => 'course-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
    }
}
