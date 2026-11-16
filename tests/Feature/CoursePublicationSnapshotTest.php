<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonQuestion;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\CoursePublicationSnapshotService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CoursePublicationSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('media');
    }

    #[Test]
    public function it_clones_the_complete_course_aggregate_and_its_media_without_sharing_source_state(): void
    {
        [$source, $sourceModule, $firstLesson, $secondLesson, $sourceQuestion, $sourceOption] = $this->sourceAggregate();
        $sourceMedia = $this->addAllRelevantMedia($source, $firstLesson, $sourceQuestion, $sourceOption);

        $snapshot = app(CoursePublicationSnapshotService::class)->createSnapshot($source);
        $snapshot->load('modules.lessons.questions.options');

        $snapshotModule = $snapshot->modules->sole();
        $snapshotFirstLesson = $snapshotModule->lessons->firstWhere('order_column', $firstLesson->order_column);
        $snapshotSecondLesson = $snapshotModule->lessons->firstWhere('order_column', $secondLesson->order_column);
        $snapshotQuestion = $snapshotFirstLesson->questions->sole();
        $snapshotOption = $snapshotQuestion->options->sole();

        $this->assertNotSame($source->id, $snapshot->id);
        $this->assertSame($source->id, $snapshot->source_course_id);
        $this->assertTrue($snapshot->is_publication_snapshot);
        $this->assertNotSame($sourceModule->id, $snapshotModule->id);
        $this->assertNotSame($firstLesson->id, $snapshotFirstLesson->id);
        $this->assertNotSame($sourceQuestion->id, $snapshotQuestion->id);
        $this->assertNotSame($sourceOption->id, $snapshotOption->id);
        $this->assertSame($snapshot->id, $snapshotModule->course_id);
        $this->assertSame($snapshotModule->id, $snapshotFirstLesson->course_module_id);
        $this->assertSame($snapshotFirstLesson->id, $snapshotQuestion->course_lesson_id);
        $this->assertSame($snapshotQuestion->id, $snapshotOption->lesson_question_id);
        $this->assertSame($snapshotSecondLesson->id, $snapshotQuestion->correct_target_lesson_id);
        $this->assertSame($snapshotFirstLesson->id, $snapshotQuestion->wrong_target_lesson_id);
        $this->assertSame($snapshotSecondLesson->id, $snapshotOption->correct_target_lesson_id);
        $this->assertSame($snapshotFirstLesson->id, $snapshotOption->wrong_target_lesson_id);
        $this->assertNotContains($firstLesson->id, [$snapshotQuestion->correct_target_lesson_id, $snapshotQuestion->wrong_target_lesson_id]);
        $this->assertNotContains($secondLesson->id, [$snapshotQuestion->correct_target_lesson_id, $snapshotOption->correct_target_lesson_id]);

        $snapshotMedia = collect([
            $snapshot,
            $snapshotFirstLesson,
            $snapshotQuestion,
            $snapshotOption,
        ])->flatMap(fn ($model) => $model->getMedia('*'));
        $this->assertCount($sourceMedia->count(), $snapshotMedia);

        foreach ($sourceMedia as $media) {
            Storage::disk('media')->assertExists($media->id.'/'.$media->file_name);
        }
        foreach ($snapshotMedia as $media) {
            Storage::disk('media')->assertExists($media->id.'/'.$media->file_name);
        }

        $sourceThumbnail = $source->getFirstMedia('thumbnail');
        $snapshotThumbnail = $snapshot->getFirstMedia('thumbnail');
        $this->assertNotSame($sourceThumbnail->id, $snapshotThumbnail->id);
        $this->assertNotSame($sourceThumbnail->id.'/'.$sourceThumbnail->file_name, $snapshotThumbnail->id.'/'.$snapshotThumbnail->file_name);
        $this->assertSame($sourceThumbnail->collection_name, $snapshotThumbnail->collection_name);
        $this->assertSame($sourceThumbnail->disk, $snapshotThumbnail->disk);
        $this->assertSame($sourceThumbnail->file_name, $snapshotThumbnail->file_name);
        $this->assertSame($sourceThumbnail->order_column, $snapshotThumbnail->order_column);
        $this->assertSame($sourceThumbnail->custom_properties, $snapshotThumbnail->custom_properties);
        $this->assertSame($sourceThumbnail->manipulations, $snapshotThumbnail->manipulations);
        $this->assertSame('thumbnail-source', Storage::disk('media')->get($snapshotThumbnail->id.'/'.$snapshotThumbnail->file_name));

        $sourceThumbnail->delete();
        Storage::disk('media')->assertMissing($sourceThumbnail->id.'/'.$sourceThumbnail->file_name);
        Storage::disk('media')->assertExists($snapshotThumbnail->id.'/'.$snapshotThumbnail->file_name);

        $source->addMediaFromString('thumbnail-replacement')
            ->usingFileName('thumbnail.txt')
            ->toMediaCollection('thumbnail', 'media');
        $this->assertSame('thumbnail-source', Storage::disk('media')->get($snapshotThumbnail->id.'/'.$snapshotThumbnail->file_name));

        $firstLesson->update(['title' => 'Source changed after snapshot']);
        $this->assertSame('Lesson one', $snapshotFirstLesson->fresh()->title);
    }

    #[Test]
    public function it_compensates_structural_and_media_state_when_a_media_copy_fails(): void
    {
        [$source, , $firstLesson, , $sourceQuestion, $sourceOption] = $this->sourceAggregate();
        $this->addMedia($source, 'thumbnail', 'source-only', 'source-only.txt');
        $filesBefore = Storage::disk('media')->allFiles();

        $service = new CoursePublicationSnapshotService(function (): void {
            throw new RuntimeException('forced media copy failure');
        });

        try {
            $service->createSnapshot($source);
            $this->fail('The injected media copy failure must be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced media copy failure', $exception->getMessage());
        }

        $this->assertSame(0, Course::query()->where('source_course_id', $source->id)->count());
        $this->assertSame(0, CourseModule::query()->whereNotIn('id', [$source->modules->sole()->id])->count());
        $this->assertSame(1, $source->fresh()->getMedia('thumbnail')->count());
        $this->assertSame($filesBefore, Storage::disk('media')->allFiles());
        $this->assertSame('Lesson one', $firstLesson->fresh()->title);
        $this->assertSame($firstLesson->id, $sourceQuestion->fresh()->course_lesson_id);
        $this->assertSame($sourceQuestion->id, $sourceOption->fresh()->lesson_question_id);
    }

    #[Test]
    public function snapshots_are_hidden_from_course_administration_and_denied_to_normal_edit_routes(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $master = User::factory()->create();
        $master->assignRole('Master');
        [$source] = $this->sourceAggregate();
        $snapshot = Course::create([
            'company_id' => $source->company_id,
            'user_id' => $source->user_id,
            'source_course_id' => $source->id,
            'title' => 'Hidden snapshot',
            'slug' => 'hidden-snapshot-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
        $snapshot->forceFill(['is_publication_snapshot' => true])->save();

        $this->actingAs($master)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('courses.data', 1));
        $this->actingAs($master)->get(route('admin.courses.edit', $snapshot))->assertForbidden();
        $this->actingAs($master)->get(route('admin.courses.builder.index', $snapshot))->assertForbidden();
    }

    /** @return array{Course, CourseModule, CourseLesson, CourseLesson, LessonQuestion, QuestionOption} */
    private function sourceAggregate(): array
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $source = Course::create([
            'company_id' => $company->id,
            'user_id' => $owner->id,
            'title' => 'Source course',
            'slug' => 'source-course-'.uniqid(),
            'headline' => 'Source headline',
            'description' => 'Source description',
            'price' => 15,
            'level' => 'advanced',
            'duration_minutes' => 120,
        ]);
        $module = CourseModule::create(['course_id' => $source->id, 'title' => 'Module', 'order_column' => 1]);
        $firstLesson = CourseLesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson one',
            'slug' => 'lesson-one-'.uniqid(),
            'video_provider' => 'upload',
            'duration_minutes' => 15,
            'order_column' => 1,
        ]);
        $secondLesson = CourseLesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson two',
            'slug' => 'lesson-two-'.uniqid(),
            'duration_minutes' => 20,
            'order_column' => 2,
        ]);
        $question = LessonQuestion::create([
            'course_lesson_id' => $firstLesson->id,
            'title' => 'Question',
            'timestamp_seconds' => 12.5,
            'type' => 'single_choice',
            'loop_seconds' => 0,
            'correct_target_lesson_id' => $secondLesson->id,
            'wrong_target_lesson_id' => $firstLesson->id,
            'modal_coordinates' => ['left' => 10],
        ]);
        $option = QuestionOption::create([
            'lesson_question_id' => $question->id,
            'text' => 'Option',
            'is_correct' => true,
            'coordinates' => ['left' => 20],
            'correct_target_lesson_id' => $secondLesson->id,
            'wrong_target_lesson_id' => $firstLesson->id,
        ]);

        return [$source, $module, $firstLesson, $secondLesson, $question, $option];
    }

    private function addAllRelevantMedia(Course $source, CourseLesson $lesson, LessonQuestion $question, QuestionOption $option)
    {
        $media = collect();
        $media->push($this->addMedia($source, 'thumbnail', 'thumbnail-source', 'thumbnail.txt', ['origin' => 'course'], ['*' => ['rotate' => [90]]]));
        $media->push($this->addMedia($lesson, 'attachments', 'attachment-source', 'attachment.txt'));
        $media->push($this->addMedia($lesson, 'video_file', 'video-source', 'video.txt'));
        foreach (['background', 'title', 'modal', 'feedback_correct', 'feedback_wrong', 'confirmation'] as $collection) {
            $media->push($this->addMedia($question, $collection, $collection.'-source', $collection.'.txt'));
        }
        $media->push($this->addMedia($option, 'image', 'image-source', 'image.txt'));
        $media->push($this->addMedia($option, 'zone', 'zone-source', 'zone.txt'));

        return $media;
    }

    private function addMedia($model, string $collection, string $contents, string $fileName, array $customProperties = [], array $manipulations = [])
    {
        $adder = $model->addMediaFromString($contents)
            ->usingFileName($fileName)
            ->withCustomProperties($customProperties)
            ->withManipulations($manipulations);

        return $adder->toMediaCollection($collection, 'media');
    }
}
