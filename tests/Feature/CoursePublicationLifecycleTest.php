<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePublication;
use App\Models\Project;
use App\Models\User;
use App\Services\CoursePublicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class CoursePublicationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('media');
    }

    #[Test]
    public function an_approved_project_publishes_an_immutable_first_revision_with_a_stable_secret_token(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->approvedProject();

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.publish', $project))
            ->assertRedirect();

        $project->refresh();
        $publication = $project->activePublication()->firstOrFail();
        $this->assertSame(1, $publication->revision_number);
        $this->assertSame(CoursePublication::ACTIVE, $publication->state);
        $this->assertNotNull($publication->published_at);
        $this->assertSame($adm->id, $publication->created_by);
        $this->assertSame($course->id, $publication->source_course_id);
        $this->assertTrue($publication->snapshotCourse->is_publication_snapshot);
        $this->assertSame($course->id, $publication->snapshotCourse->source_course_id);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{32}$/', $project->token);
        $this->assertSame(1, $project->publications()->where('state', CoursePublication::ACTIVE)->count());

        $activity = Activity::query()->where('event', 'publication_created')->firstOrFail();
        $this->assertSame($project->id, $activity->properties->get('project_id'));
        $this->assertStringNotContainsString($project->token, $activity->properties->toJson());
        $this->assertStringNotContainsString($project->token, (string) $activity->description);
        $this->assertArrayNotHasKey('token', (new \App\Http\Resources\ProjectResource($project))->toArray(request()));
    }

    #[Test]
    public function publish_and_revoke_are_limited_to_master_or_the_same_company_adm(): void
    {
        [$company, $editor, $adm, , $project] = $this->approvedProject();
        $otherCompany = Company::factory()->create();
        $otherAdm = $this->userInCompany($otherCompany, 'ADM Cliente PJ');
        $master = User::factory()->create();
        $master->assignRole('Master');

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.publish', $project))->assertForbidden();
        $this->actingAs($otherAdm)->withSession(['active_company_id' => $otherCompany->id])
            ->post(route('projects.publish', $project))->assertForbidden();
        $this->actingAs($master)->post(route('projects.publish', $project))->assertRedirect();

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.revoke', $project))->assertForbidden();
        $this->actingAs($otherAdm)->withSession(['active_company_id' => $otherCompany->id])
            ->post(route('projects.revoke', $project))->assertForbidden();
        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.revoke', $project))->assertRedirect();

        $publication = $project->fresh()->publications()->sole();
        $this->assertSame(CoursePublication::REVOKED, $publication->state);
        $this->assertNotNull($publication->revoked_at);
        $this->assertSame(Project::STATUS_APPROVED, $project->fresh()->status);
        $token = $project->fresh()->token;
        $this->actingAs($master)->post(route('projects.publish', $project))->assertRedirect();
        $this->assertSame($token, $project->fresh()->token);
        $this->assertSame(2, $project->publications()->count());
        $this->assertSame(2, $project->fresh()->activePublication->revision_number);
        $this->assertDatabaseHas('activity_log', ['event' => 'publication_revoked']);
    }

    #[Test]
    public function source_edits_return_an_approved_project_to_editing_without_touching_its_active_publication_and_republish_replaces_it(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->approvedProject();
        $service = app(CoursePublicationService::class);
        $first = $service->publish($project, $adm);
        $token = $project->fresh()->token;

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->put(route('admin.courses.update', $course), [
                'title' => 'Changed source',
                'slug' => $course->slug,
                'price' => 0,
                'level' => 'beginner',
            ])->assertRedirect();

        $this->assertSame(Project::STATUS_DRAFT, $project->fresh()->status);
        $this->assertSame(CoursePublication::ACTIVE, $first->fresh()->state);
        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))->assertRedirect();
        $this->assertSame(CoursePublication::ACTIVE, $first->fresh()->state);

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.reject', $project), ['feedback' => 'Adjust'])->assertRedirect();
        $this->assertSame(CoursePublication::ACTIVE, $first->fresh()->state);
        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->put(route('admin.courses.update', $course), [
                'title' => 'Corrected source',
                'slug' => $course->slug,
                'price' => 0,
                'level' => 'beginner',
            ])->assertRedirect();
        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.submit', $course))->assertRedirect();
        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.approve', $project))->assertRedirect();

        $second = $service->publish($project->fresh(), $adm);
        $this->assertSame(2, $second->revision_number);
        $this->assertSame(CoursePublication::REPLACED, $first->fresh()->state);
        $this->assertSame(CoursePublication::ACTIVE, $second->state);
        $this->assertSame($token, $project->fresh()->token);
        $this->assertSame(1, $project->publications()->where('state', CoursePublication::ACTIVE)->count());
        $this->assertDatabaseHas('activity_log', ['event' => 'publication_replaced']);
    }

    #[Test]
    public function invalid_projects_cannot_publish_and_a_failed_second_publication_preserves_the_active_revision_without_orphans(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->approvedProject();
        $service = app(CoursePublicationService::class);

        foreach ([Project::STATUS_DRAFT, Project::STATUS_PENDING_APPROVAL, Project::STATUS_REJECTED] as $status) {
            $project->update(['status' => $status]);
            try {
                $service->publish($project->fresh(), $adm);
                $this->fail('Only approved projects may publish.');
            } catch (\DomainException) {
                $this->assertSame(0, $project->publications()->count());
            }
        }

        $project->update(['status' => Project::STATUS_APPROVED, 'course_id' => null]);
        try {
            $service->publish($project->fresh(), $adm);
            $this->fail('A project without a source course may not publish.');
        } catch (\DomainException) {
            $this->assertSame(0, $project->publications()->count());
        }
    }

    #[Test]
    public function a_failed_republish_keeps_the_previous_publication_active_and_cleans_the_new_snapshot(): void
    {
        [, , $adm, $course, $project] = $this->approvedProject();
        $course->addMediaFromString('source media')->usingFileName('source.txt')->toMediaCollection('thumbnail', 'media');
        $service = app(CoursePublicationService::class);
        $first = $service->publish($project, $adm);
        $snapshotsBefore = Course::query()->where('source_course_id', $course->id)->count();
        $filesBefore = Storage::disk('media')->allFiles();

        try {
            $service->publish($project->fresh(), new User());
            $this->fail('A publication without a persisted actor must fail.');
        } catch (\Throwable) {
            // The assertion below verifies the lifecycle compensation.
        }

        $this->assertSame(CoursePublication::ACTIVE, $first->fresh()->state);
        $this->assertSame($snapshotsBefore, Course::query()->where('source_course_id', $course->id)->count());
        $this->assertSame(1, $project->publications()->count());
        $this->assertSame($filesBefore, Storage::disk('media')->allFiles());
        $this->assertSame(Project::STATUS_APPROVED, $project->fresh()->status);
    }

    #[Test]
    public function an_existing_project_token_is_reused_and_all_authorized_edit_integration_points_are_wired(): void
    {
        [, , $adm, , $project] = $this->approvedProject();
        $project->forceFill(['token' => 'stable-project-token'])->save();

        app(CoursePublicationService::class)->publish($project, $adm);
        $this->assertSame('stable-project-token', $project->fresh()->token);

        $builder = file_get_contents(app_path('Http/Controllers/Admin/CourseBuilderController.php'));
        $this->assertSame(11, substr_count($builder, 'CoursePublicationService::class)->markSourceAsEditing($course)'));
        $courseController = file_get_contents(app_path('Http/Controllers/Admin/CourseController.php'));
        $this->assertSame(1, substr_count($courseController, 'CoursePublicationService::class)->markSourceAsEditing($course)'));
    }

    private function approvedProject(): array
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $adm = $this->userInCompany($company, 'ADM Cliente PJ');
        $course = Course::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'title' => 'Lifecycle source',
            'slug' => 'lifecycle-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
        $project = Project::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'course_id' => $course->id,
            'title' => $course->title,
            'type' => 'course',
            'status' => Project::STATUS_APPROVED,
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
