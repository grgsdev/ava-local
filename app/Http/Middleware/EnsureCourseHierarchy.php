<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonQuestion;
use App\Models\QuestionOption;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourseHierarchy
{
    public function handle(Request $request, Closure $next): Response
    {
        $course = $request->route('course');

        if (! $course instanceof Course) {
            abort(404);
        }

        $module = $request->route('module');
        if ($module instanceof CourseModule && (int) $module->course_id !== (int) $course->id) {
            abort(404);
        }

        $lesson = $request->route('lesson');
        if ($lesson instanceof CourseLesson && ! $this->lessonBelongsToCourse($lesson, $course)) {
            abort(404);
        }

        $question = $request->route('question');
        if ($question instanceof LessonQuestion && ! $this->questionBelongsToCourse($question, $course)) {
            abort(404);
        }

        $option = $request->route('option');
        if ($option instanceof QuestionOption && ! $this->optionBelongsToCourse($option, $course)) {
            abort(404);
        }

        return $next($request);
    }

    private function lessonBelongsToCourse(CourseLesson $lesson, Course $course): bool
    {
        return CourseModule::query()
            ->whereKey($lesson->course_module_id)
            ->where('course_id', $course->id)
            ->exists();
    }

    private function questionBelongsToCourse(LessonQuestion $question, Course $course): bool
    {
        return CourseLesson::query()
            ->whereKey($question->course_lesson_id)
            ->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->exists();
    }

    private function optionBelongsToCourse(QuestionOption $option, Course $course): bool
    {
        return LessonQuestion::query()
            ->whereKey($option->lesson_question_id)
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
            ->exists();
    }
}
