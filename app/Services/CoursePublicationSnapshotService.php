<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonQuestion;
use App\Models\QuestionOption;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class CoursePublicationSnapshotService
{
    /** @var null|Closure(Media, Media): void */
    private readonly ?Closure $afterMediaCopied;

    public function __construct(?Closure $afterMediaCopied = null)
    {
        $this->afterMediaCopied = $afterMediaCopied;
    }

    public function createSnapshot(Course $source): Course
    {
        if ($source->is_publication_snapshot) {
            throw new \InvalidArgumentException('A publication snapshot cannot be cloned as a source course.');
        }

        $source->loadMissing('modules.lessons.questions.options');

        $sourceLessons = $source->modules
            ->flatMap(fn (CourseModule $module) => $module->lessons)
            ->keyBy('id');
        $this->assertAllTargetsBelongToSource($source, $sourceLessons->keys()->all());

        /** @var list<Media> $createdMedia */
        $createdMedia = [];
        /** @var list<Model> $createdModels */
        $createdModels = [];

        DB::beginTransaction();

        try {
            $snapshot = $source->replicate();
            $snapshot->source_course_id = $source->id;
            $snapshot->is_publication_snapshot = true;
            $snapshot->deleted_at = null;
            $snapshot->slug = $this->snapshotSlug($source);
            $snapshot->save();
            $createdModels[] = $snapshot;

            $moduleMap = [];
            $lessonMap = [];
            $questionMap = [];
            $optionMap = [];
            $questionTargets = [];
            $optionTargets = [];

            foreach ($source->modules as $module) {
                $snapshotModule = $module->replicate();
                $snapshotModule->course_id = $snapshot->id;
                $snapshotModule->save();
                $createdModels[] = $snapshotModule;
                $moduleMap[$module->id] = $snapshotModule;

                foreach ($module->lessons as $lesson) {
                    $snapshotLesson = $lesson->replicate();
                    $snapshotLesson->course_module_id = $snapshotModule->id;
                    $snapshotLesson->save();
                    $createdModels[] = $snapshotLesson;
                    $lessonMap[$lesson->id] = $snapshotLesson;
                }
            }

            foreach ($source->modules as $module) {
                foreach ($module->lessons as $lesson) {
                    foreach ($lesson->questions as $question) {
                        $snapshotQuestion = $question->replicate();
                        $snapshotQuestion->course_lesson_id = $lessonMap[$lesson->id]->id;
                        $snapshotQuestion->correct_target_lesson_id = null;
                        $snapshotQuestion->wrong_target_lesson_id = null;
                        $snapshotQuestion->save();
                        $createdModels[] = $snapshotQuestion;
                        $questionMap[$question->id] = $snapshotQuestion;
                        $questionTargets[$question->id] = $this->targetAttributes($question);

                        foreach ($question->options as $option) {
                            $snapshotOption = $option->replicate();
                            $snapshotOption->lesson_question_id = $snapshotQuestion->id;
                            $snapshotOption->correct_target_lesson_id = null;
                            $snapshotOption->wrong_target_lesson_id = null;
                            $snapshotOption->save();
                            $createdModels[] = $snapshotOption;
                            $optionMap[$option->id] = $snapshotOption;
                            $optionTargets[$option->id] = $this->targetAttributes($option);
                        }
                    }
                }
            }

            foreach ($questionMap as $sourceQuestionId => $snapshotQuestion) {
                $snapshotQuestion->update($this->remappedTargets($questionTargets[$sourceQuestionId], $lessonMap));
            }

            foreach ($optionMap as $sourceOptionId => $snapshotOption) {
                $snapshotOption->update($this->remappedTargets($optionTargets[$sourceOptionId], $lessonMap));
            }

            $this->copyMedia($source, $snapshot, $createdMedia);
            foreach ($lessonMap as $sourceLessonId => $snapshotLesson) {
                $this->copyMedia($sourceLessons[$sourceLessonId], $snapshotLesson, $createdMedia);
            }
            foreach ($questionMap as $sourceQuestionId => $snapshotQuestion) {
                $this->copyMedia($this->findQuestion($source, $sourceQuestionId), $snapshotQuestion, $createdMedia);
            }
            foreach ($optionMap as $sourceOptionId => $snapshotOption) {
                $this->copyMedia($this->findOption($source, $sourceOptionId), $snapshotOption, $createdMedia);
            }

            DB::commit();

            return $snapshot->fresh(['modules.lessons.questions.options']);
        } catch (Throwable $exception) {
            foreach (array_reverse($createdMedia) as $media) {
                try {
                    $media->delete();
                } catch (Throwable) {
                    // The database transaction and the remaining aggregate cleanup still run.
                }
            }

            foreach (array_reverse($createdModels) as $model) {
                try {
                    $model->forceDelete();
                } catch (Throwable) {
                    // The transaction rollback is the final structural compensation.
                }
            }

            DB::rollBack();

            throw $exception;
        }
    }

    /**
     * Compensates a snapshot that was successfully cloned but could not be
     * attached to a publication by the surrounding lifecycle transaction.
     */
    public function discardSnapshot(Course $snapshot): void
    {
        $snapshot->loadMissing('modules.lessons.questions.options');

        $models = [
            ...$snapshot->modules->flatMap(fn (CourseModule $module) => $module->lessons)
                ->flatMap(fn (CourseLesson $lesson) => $lesson->questions)
                ->flatMap(fn (LessonQuestion $question) => $question->options),
            ...$snapshot->modules->flatMap(fn (CourseModule $module) => $module->lessons)
                ->flatMap(fn (CourseLesson $lesson) => $lesson->questions),
            ...$snapshot->modules->flatMap(fn (CourseModule $module) => $module->lessons),
            $snapshot,
        ];

        foreach ($models as $model) {
            foreach ($model->getMedia('*') as $media) {
                $media->delete();
            }
        }

        $snapshot->forceDelete();
    }

    /** @param list<int> $sourceLessonIds */
    private function assertAllTargetsBelongToSource(Course $source, array $sourceLessonIds): void
    {
        $sourceLessonIds = array_map('intval', $sourceLessonIds);

        foreach ($source->modules as $module) {
            foreach ($module->lessons as $lesson) {
                foreach ($lesson->questions as $question) {
                    foreach ([$question, ...$question->options] as $targetable) {
                        foreach ($this->targetAttributes($targetable) as $targetId) {
                            if ($targetId !== null && ! in_array((int) $targetId, $sourceLessonIds, true)) {
                                throw new \LogicException('A snapshot cannot retain a lesson target outside its source aggregate.');
                            }
                        }
                    }
                }
            }
        }
    }

    /** @return array{correct_target_lesson_id: int|null, wrong_target_lesson_id: int|null} */
    private function targetAttributes(LessonQuestion|QuestionOption $model): array
    {
        return [
            'correct_target_lesson_id' => $model->correct_target_lesson_id,
            'wrong_target_lesson_id' => $model->wrong_target_lesson_id,
        ];
    }

    /** @param array{correct_target_lesson_id: int|null, wrong_target_lesson_id: int|null} $targets @param array<int, CourseLesson> $lessonMap */
    private function remappedTargets(array $targets, array $lessonMap): array
    {
        return [
            'correct_target_lesson_id' => $targets['correct_target_lesson_id'] === null ? null : $lessonMap[$targets['correct_target_lesson_id']]->id,
            'wrong_target_lesson_id' => $targets['wrong_target_lesson_id'] === null ? null : $lessonMap[$targets['wrong_target_lesson_id']]->id,
        ];
    }

    /** @param list<Media> $createdMedia */
    private function copyMedia(HasMedia $source, HasMedia $destination, array &$createdMedia): void
    {
        foreach ($source->getMedia('*') as $media) {
            $copiedMedia = $media->copy($destination, $media->collection_name, $media->disk, $media->file_name);
            $createdMedia[] = $copiedMedia;

            if ($this->afterMediaCopied !== null) {
                ($this->afterMediaCopied)($media, $copiedMedia);
            }
        }
    }

    private function snapshotSlug(Course $source): string
    {
        return Str::limit((string) $source->slug, 209, '').'-snapshot-'.Str::uuid();
    }

    private function findQuestion(Course $source, int $questionId): LessonQuestion
    {
        return $source->modules
            ->flatMap(fn (CourseModule $module) => $module->lessons)
            ->flatMap(fn (CourseLesson $lesson) => $lesson->questions)
            ->firstWhere('id', $questionId);
    }

    private function findOption(Course $source, int $optionId): QuestionOption
    {
        return $source->modules
            ->flatMap(fn (CourseModule $module) => $module->lessons)
            ->flatMap(fn (CourseLesson $lesson) => $lesson->questions)
            ->flatMap(fn (LessonQuestion $question) => $question->options)
            ->firstWhere('id', $optionId);
    }
}
