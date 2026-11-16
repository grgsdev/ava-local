<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\CoursePublication;
use App\Models\LessonQuestion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoursePublicationPublicAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('media');
    }

    #[Test]
    public function a_public_token_resolves_only_the_active_snapshot_and_keeps_serving_it_during_source_workflow_changes(): void
    {
        [$company, $source, $project, $active, $snapshot, $lesson] = $this->publication('published-token');
        $source->update(['title' => 'Changed mutable source']);

        foreach ([Project::STATUS_DRAFT, Project::STATUS_PENDING_APPROVAL, Project::STATUS_REJECTED] as $status) {
            $project->update(['status' => $status]);
            $this->get(route('publications.show', 'published-token'))
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Student/StudentLesson')
                    ->where('course.title', 'Snapshot course')
                    ->where('lesson.title', 'Snapshot lesson')
                    ->missing('course.id')
                    ->missing('course.source_course_id'));
        }

        $this->get(route('publications.lessons.show', ['token' => 'published-token', 'lesson' => $lesson]))
            ->assertOk()
            ->assertJsonPath('lesson.title', 'Snapshot lesson')
            ->assertJsonMissing(['course_module_id' => $lesson->course_module_id])
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNotSame($source->title, $snapshot->title);
        $this->assertSame(CoursePublication::ACTIVE, $active->fresh()->state);
    }

    #[Test]
    public function unavailable_or_inconsistent_publications_and_secondary_lesson_ids_are_not_served(): void
    {
        $this->get(route('publications.show', 'missing-token'))->assertNotFound();

        [, , $project, $publication, $snapshot, $lesson] = $this->publication('guarded-token');
        $publication->update(['state' => CoursePublication::REVOKED]);
        $this->get(route('publications.show', 'guarded-token'))->assertNotFound();
        $publication->update(['state' => CoursePublication::REPLACED]);
        $this->get(route('publications.show', 'guarded-token'))->assertNotFound();

        $publication->update(['state' => CoursePublication::ACTIVE]);
        $otherCourse = Course::create([
            'company_id' => $project->company_id, 'user_id' => $project->user_id,
            'title' => 'Other course', 'slug' => 'other-'.uniqid(), 'price' => 0, 'level' => 'beginner',
        ]);
        $otherLesson = CourseLesson::create([
            'course_module_id' => CourseModule::create(['course_id' => $otherCourse->id, 'title' => 'Other', 'order_column' => 1])->id,
            'title' => 'Other lesson', 'slug' => 'other-'.uniqid(), 'order_column' => 1,
        ]);
        $this->get(route('publications.lessons.show', ['token' => 'guarded-token', 'lesson' => $otherLesson]))->assertNotFound();

        $snapshot->update(['source_course_id' => null]);
        $this->get(route('publications.show', 'guarded-token'))->assertNotFound();
    }

    #[Test]
    public function the_stable_url_switches_to_the_new_active_snapshot_and_is_disabled_by_revoke(): void
    {
        [, $source, $project, $first, $firstSnapshot] = $this->publication('stable-token');
        $secondSnapshot = $this->snapshot($source, 'Second snapshot', 'Second lesson');
        $second = CoursePublication::create([
            'project_id' => $project->id,
            'source_course_id' => $source->id,
            'snapshot_course_id' => $secondSnapshot->id,
            'revision_number' => 2,
            'state' => CoursePublication::ACTIVE,
            'published_at' => now(),
            'created_by' => $project->user_id,
        ]);
        $first->update(['state' => CoursePublication::REPLACED]);

        $this->get(route('publications.show', 'stable-token'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('course.title', 'Second snapshot'));
        $this->assertSame('stable-token', $project->fresh()->token);

        $second->update(['state' => CoursePublication::REVOKED, 'revoked_at' => now()]);
        $this->get(route('publications.show', 'stable-token'))->assertNotFound();

        $thirdSnapshot = $this->snapshot($source, 'Third snapshot', 'Third lesson');
        CoursePublication::create([
            'project_id' => $project->id,
            'source_course_id' => $source->id,
            'snapshot_course_id' => $thirdSnapshot->id,
            'revision_number' => 3,
            'state' => CoursePublication::ACTIVE,
            'published_at' => now(),
            'created_by' => $project->user_id,
        ]);
        $this->get(route('publications.show', 'stable-token'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('course.title', 'Third snapshot'));
    }

    #[Test]
    public function csp_allows_only_a_valid_configured_origin_and_is_safe_for_null_or_invalid_values(): void
    {
        [$company] = $this->publication('csp-token');
        $company->update(['authorized_embed_origin' => 'https://client.example:8443']);
        $this->get(route('publications.show', 'csp-token'))
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self' https://client.example:8443")
            ->assertHeaderMissing('X-Frame-Options');

        $company->update(['authorized_embed_origin' => null]);
        $this->get(route('publications.show', 'csp-token'))
            ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        foreach (['https://client.example/path', 'https://client.example?x=1', 'javascript:alert(1)', 'data:text/plain,test', 'https://*.example'] as $invalid) {
            $company->update(['authorized_embed_origin' => $invalid]);
            $this->get(route('publications.show', 'csp-token'))
                ->assertOk()
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        }
    }

    private function publication(string $token): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $source = Course::create([
            'company_id' => $company->id, 'user_id' => $owner->id, 'title' => 'Source course',
            'slug' => 'source-'.uniqid(), 'price' => 0, 'level' => 'beginner',
        ]);
        $project = Project::create([
            'company_id' => $company->id, 'user_id' => $owner->id, 'course_id' => $source->id,
            'title' => 'Project', 'type' => 'course', 'status' => Project::STATUS_APPROVED,
        ]);
        $project->forceFill(['token' => $token])->save();
        $snapshot = $this->snapshot($source, 'Snapshot course', 'Snapshot lesson');
        $lesson = $snapshot->modules()->firstOrFail()->lessons()->firstOrFail();
        $publication = CoursePublication::create([
            'project_id' => $project->id, 'source_course_id' => $source->id,
            'snapshot_course_id' => $snapshot->id, 'revision_number' => 1,
            'state' => CoursePublication::ACTIVE, 'published_at' => now(), 'created_by' => $owner->id,
        ]);

        return [$company, $source, $project, $publication, $snapshot, $lesson];
    }

    private function snapshot(Course $source, string $courseTitle, string $lessonTitle): Course
    {
        $snapshot = Course::create([
            'company_id' => $source->company_id, 'user_id' => $source->user_id,
            'source_course_id' => $source->id,
            'title' => $courseTitle, 'slug' => 'snapshot-'.uniqid(), 'price' => 0, 'level' => 'beginner',
        ]);
        $snapshot->forceFill(['is_publication_snapshot' => true])->save();
        $module = CourseModule::create(['course_id' => $snapshot->id, 'title' => 'Snapshot module', 'order_column' => 1]);
        CourseLesson::create([
            'course_module_id' => $module->id, 'title' => $lessonTitle,
            'slug' => 'lesson-'.uniqid(), 'video_url' => 'https://example.test/video.mp4', 'order_column' => 1,
        ]);

        return $snapshot;
    }
}
