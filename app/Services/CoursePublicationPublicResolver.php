<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CoursePublication;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CoursePublicationPublicResolver
{
    public function resolve(string $token): array
    {
        $project = Project::query()
            ->where('token', $token)
            ->with(['course', 'company', 'activePublication.snapshotCourse'])
            ->first();

        $publication = $project?->activePublication;
        $snapshot = $publication?->snapshotCourse;

        if (! $project instanceof Project
            || ! $publication instanceof CoursePublication
            || $publication->state !== CoursePublication::ACTIVE
            || ! $snapshot instanceof Course
            || ! $snapshot->is_publication_snapshot
            || (int) $publication->source_course_id !== (int) $project->course_id
            || (int) $publication->snapshot_course_id !== (int) $snapshot->id
            || (int) $snapshot->source_course_id !== (int) $publication->source_course_id
            || (int) $snapshot->company_id !== (int) $project->company_id
            || (int) $project->course?->company_id !== (int) $project->company_id) {
            throw (new ModelNotFoundException())->setModel(Project::class);
        }

        return [$project, $publication, $snapshot];
    }

    public function resolveLesson(string $token, int $lessonId): array
    {
        [, $publication, $snapshot] = $this->resolve($token);

        $lesson = CourseLesson::query()
            ->whereKey($lessonId)
            ->whereHas('module', fn ($query) => $query->where('course_id', $snapshot->id))
            ->first();

        if (! $lesson instanceof CourseLesson) {
            throw (new ModelNotFoundException())->setModel(CourseLesson::class);
        }

        return [$publication, $snapshot, $lesson];
    }
}
