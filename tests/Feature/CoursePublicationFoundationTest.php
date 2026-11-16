<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePublication;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoursePublicationFoundationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_provides_the_course_publication_foundation_with_its_relations_casts_and_constraints(): void
    {
        $company = Company::factory()->create(['authorized_embed_origin' => null]);
        $owner = User::factory()->create();
        $sourceCourse = $this->course($company, $owner);
        $snapshotCourse = $this->snapshot($company, $owner, $sourceCourse);
        $project = Project::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'course_id' => $sourceCourse->id,
            'title' => 'Publication foundation project',
            'type' => 'course',
            'status' => Project::STATUS_APPROVED,
        ]);

        $publication = CoursePublication::create([
            'project_id' => $project->id,
            'source_course_id' => $sourceCourse->id,
            'snapshot_course_id' => $snapshotCourse->id,
            'revision_number' => 1,
            'state' => CoursePublication::ACTIVE,
            'created_by' => $owner->id,
        ]);

        $this->assertInstanceOf(CoursePublication::class, $publication);
        $this->assertInstanceOf(BelongsTo::class, $publication->project());
        $this->assertInstanceOf(BelongsTo::class, $publication->sourceCourse());
        $this->assertInstanceOf(BelongsTo::class, $publication->snapshotCourse());
        $this->assertInstanceOf(BelongsTo::class, $publication->createdBy());
        $this->assertSame($project->id, $publication->project->id);
        $this->assertSame($sourceCourse->id, $publication->sourceCourse->id);
        $this->assertSame($snapshotCourse->id, $publication->snapshotCourse->id);
        $this->assertSame($owner->id, $publication->createdBy->id);

        $this->assertInstanceOf(HasMany::class, $project->publications());
        $this->assertInstanceOf(HasOne::class, $project->activePublication());
        $this->assertSame($publication->id, $project->activePublication->id);
        CoursePublication::create([
            'project_id' => $project->id,
            'source_course_id' => $sourceCourse->id,
            'snapshot_course_id' => $this->snapshot($company, $owner, $sourceCourse)->id,
            'revision_number' => 2,
            'state' => 'revoked',
            'created_by' => $owner->id,
        ]);
        $this->assertCount(2, $project->publications()->get());
        $this->assertSame($publication->id, $project->fresh()->activePublication->id);

        $this->assertInstanceOf(BelongsTo::class, $snapshotCourse->sourceCourse());
        $this->assertInstanceOf(HasMany::class, $sourceCourse->publicationSnapshots());
        $this->assertSame($sourceCourse->id, $snapshotCourse->sourceCourse->id);
        $this->assertCount(2, $sourceCourse->publicationSnapshots()->get());
        $this->assertTrue($snapshotCourse->is_publication_snapshot);
        $this->assertIsBool($snapshotCourse->is_publication_snapshot);
        $this->assertFalse($sourceCourse->fresh()->is_publication_snapshot);
        $this->assertIsBool($sourceCourse->fresh()->is_publication_snapshot);
        $this->assertNull($company->fresh()->authorized_embed_origin);

        $this->expectQueryException(function () use ($project, $sourceCourse, $company, $owner): void {
            CoursePublication::create([
                'project_id' => $project->id,
                'source_course_id' => $sourceCourse->id,
                'snapshot_course_id' => $this->snapshot($company, $owner, $sourceCourse)->id,
                'revision_number' => 1,
                'state' => 'active',
                'created_by' => $owner->id,
            ]);
        });
        $this->expectQueryException(function () use ($project, $sourceCourse, $snapshotCourse, $owner): void {
            CoursePublication::create([
                'project_id' => $project->id,
                'source_course_id' => $sourceCourse->id,
                'snapshot_course_id' => $snapshotCourse->id,
                'revision_number' => 3,
                'state' => 'active',
                'created_by' => $owner->id,
            ]);
        });
    }

    private function course(Company $company, User $owner): Course
    {
        return Course::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'title' => 'Source course '.uniqid(),
            'slug' => 'source-course-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
    }

    private function snapshot(Company $company, User $owner, Course $sourceCourse): Course
    {
        $snapshot = Course::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'source_course_id' => $sourceCourse->id,
            'title' => 'Snapshot course '.uniqid(),
            'slug' => 'snapshot-course-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
        $snapshot->forceFill(['is_publication_snapshot' => true])->save();

        return $snapshot->fresh();
    }

    private function expectQueryException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('The database constraint did not reject the duplicate value.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
