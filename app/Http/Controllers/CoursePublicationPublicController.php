<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Services\CoursePublicationPublicResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;

class CoursePublicationPublicController extends Controller
{
    public function show(string $token, CoursePublicationPublicResolver $resolver): Response
    {
        [$project, , $snapshot] = $resolver->resolve($token);
        $lesson = $snapshot->modules()->with('lessons')->get()->flatMap->lessons->first();

        abort_unless($lesson instanceof CourseLesson, 404);

        return $this->publicResponse(Inertia::render('Student/StudentLesson', [
            'course' => ['title' => $snapshot->title],
            'module' => ['title' => $lesson->module->title],
            'lesson' => $this->lessonPayload($lesson, $snapshot),
        ])->toResponse(request()), $project->company?->authorized_embed_origin);
    }

    public function lesson(string $token, int $lesson, CoursePublicationPublicResolver $resolver): JsonResponse
    {
        [$publication, $snapshot, $courseLesson] = $resolver->resolveLesson($token, $lesson);

        return response()->json([
            'lesson' => $this->lessonPayload($courseLesson, $snapshot),
        ])->header('Cache-Control', 'no-store, private');
    }

    private function lessonPayload(CourseLesson $lesson, Course $snapshot): array
    {
        $lesson->loadMissing(['module', 'questions.options', 'questions.media', 'questions.options.media']);

        abort_unless((int) $lesson->module->course_id === (int) $snapshot->id, 404);

        $lesson->questions->each(function ($question): void {
            $question->background_image = $question->getFirstMediaUrl('background');
            $question->title_image = $question->getFirstMediaUrl('title');
            $question->modal_image = $question->getFirstMediaUrl('modal');
            $question->confirmation_image = $question->getFirstMediaUrl('confirmation');
            $question->confirmation_button_image = $question->getFirstMediaUrl('confirmation_button');
            $question->global_background_image = $question->getFirstMediaUrl('global_background');
            $question->unsetRelation('media');

            $question->options->each(function ($option): void {
                $option->image_url = $option->getFirstMediaUrl('image');
                $option->zone_image = $option->getFirstMediaUrl('zone');
                $option->zone_image_url = $option->zone_image;
                $option->unsetRelation('media');
            });
        });

        $payload = $lesson->toArray();
        unset($payload['course_module_id']);

        return $payload;
    }

    private function publicResponse(Response $response, ?string $authorizedOrigin): Response
    {
        $origin = $this->validOrigin($authorizedOrigin);
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'".($origin === null ? '' : ' '.$origin));
        $response->headers->set('Cache-Control', 'no-store, private');

        if ($origin === null) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        } else {
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }

    private function validOrigin(?string $value): ?string
    {
        if (! is_string($value) || $value === '' || preg_match('/[?#*]/', $value)) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['path'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return null;
        }

        $origin = strtolower($parts['scheme']).'://'.strtolower($parts['host']);

        return isset($parts['port']) ? $origin.':'.$parts['port'] : $origin;
    }
}
