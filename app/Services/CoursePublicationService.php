<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CoursePublication;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CoursePublicationService
{
    public function __construct(private readonly CoursePublicationSnapshotService $snapshots)
    {
    }

    public function markSourceAsEditing(Course $course): void
    {
        if ($course->is_publication_snapshot) {
            return;
        }

        Project::query()
            ->where('course_id', $course->id)
            ->where('status', Project::STATUS_APPROVED)
            ->update(['status' => Project::STATUS_DRAFT]);
    }

    public function publish(Project $project, User $actor): CoursePublication
    {
        $snapshot = null;

        DB::beginTransaction();

        try {
            $lockedProject = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $source = $this->eligibleSource($lockedProject);
            $previous = CoursePublication::query()
                ->where('project_id', $lockedProject->id)
                ->where('state', CoursePublication::ACTIVE)
                ->lockForUpdate()
                ->first();
            $previousState = $previous?->state;

            $snapshot = $this->snapshots->createSnapshot($source);
            $revisionNumber = (int) CoursePublication::query()
                ->where('project_id', $lockedProject->id)
                ->max('revision_number') + 1;

            if (blank($lockedProject->token)) {
                $lockedProject->forceFill(['token' => Str::random(32)])->save();
            }

            $publication = CoursePublication::create([
                'project_id' => $lockedProject->id,
                'source_course_id' => $source->id,
                'snapshot_course_id' => $snapshot->id,
                'revision_number' => $revisionNumber,
                'state' => CoursePublication::ACTIVE,
                'published_at' => now(),
                'created_by' => $actor->id,
            ]);

            if ($previous !== null) {
                $previous->update(['state' => CoursePublication::REPLACED]);
            }

            activity('project_publication')
                ->event($previous === null ? 'publication_created' : 'publication_replaced')
                ->performedOn($publication)
                ->causedBy($actor)
                ->withProperties([
                    'project_id' => $lockedProject->id,
                    'source_course_id' => $source->id,
                    'revision_number' => $publication->revision_number,
                    'from' => $previousState,
                    'to' => CoursePublication::ACTIVE,
                ])
                ->log($previous === null ? 'Publication created' : 'Publication replaced');

            DB::commit();

            return $publication->fresh();
        } catch (Throwable $exception) {
            if ($snapshot !== null) {
                try {
                    $this->snapshots->discardSnapshot($snapshot);
                } catch (Throwable) {
                    // The database rollback remains the final structural compensation.
                }
            }

            DB::rollBack();

            throw $exception;
        }
    }

    public function revoke(Project $project, User $actor): CoursePublication
    {
        return DB::transaction(function () use ($project, $actor): CoursePublication {
            $lockedProject = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            $publication = CoursePublication::query()
                ->where('project_id', $lockedProject->id)
                ->where('state', CoursePublication::ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            $publication->update([
                'state' => CoursePublication::REVOKED,
                'revoked_at' => now(),
            ]);

            activity('project_publication')
                ->event('publication_revoked')
                ->performedOn($publication)
                ->causedBy($actor)
                ->withProperties([
                    'project_id' => $lockedProject->id,
                    'source_course_id' => $publication->source_course_id,
                    'revision_number' => $publication->revision_number,
                    'from' => CoursePublication::ACTIVE,
                    'to' => CoursePublication::REVOKED,
                ])
                ->log('Publication revoked');

            return $publication->fresh();
        });
    }

    private function eligibleSource(Project $project): Course
    {
        if ($project->status !== Project::STATUS_APPROVED) {
            throw new \DomainException('Project is not approved for publication.');
        }

        $source = $project->course;
        if ($source === null || $source->is_publication_snapshot || (int) $source->company_id !== (int) $project->company_id) {
            throw new \DomainException('Project has no eligible source course for publication.');
        }

        return $source;
    }
}
