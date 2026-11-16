<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\LessonQuestion;
use App\Models\QuestionOption;
use App\Services\CoursePublicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class CourseBuilderController extends Controller
{
    public function index(Course $course)
    {
        $course->load(['modules.lessons', 'modules.lessons.questions.options']);

        return Inertia::render('Admin/Courses/Builder/Index', [
            'course' => $course
        ]);
    }

    public function storeModule(Request $request, Course $course)
    {
        $validated = $request->validate(['title' => 'required|string|max:255']);

        app(CoursePublicationService::class)->markSourceAsEditing($course);

        $course->modules()->create([
            'title' => $validated['title'],
            'order_column' => $course->modules()->count() + 1
        ]);

        return back()->with('success', 'Módulo criado.');
    }

    public function storeLesson(Request $request, Course $course, CourseModule $module)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url'
        ]);

        app(CoursePublicationService::class)->markSourceAsEditing($course);

        $module->lessons()->create([
            'title' => $validated['title'],
            'slug' => \Illuminate\Support\Str::slug($validated['title']),
            'video_url' => $validated['video_url'],
            'video_provider' => 'external',
            'order_column' => $module->lessons()->count() + 1
        ]);

        return back()->with('success', 'Aula adicionada.');
    }

    public function editLesson(Course $course, CourseModule $module, CourseLesson $lesson)
    {
        $lesson->load(['questions.options']);
        $lesson->questions->each(function ($q) {
            $q->background_image = $q->getFirstMediaUrl('background');
            $q->title_image = $q->getFirstMediaUrl('title');
            $q->modal_image = $q->getFirstMediaUrl('modal');
            $q->confirmation_image = $q->getFirstMediaUrl('confirmation');
            $q->confirmation_button_image = $q->getFirstMediaUrl('confirmation_button');
            $q->global_background_image = $q->getFirstMediaUrl('global_background');
            $q->options->each(function ($o) {
                $o->image_url = $o->getFirstMediaUrl('image');
                $o->zone_image = $o->getFirstMediaUrl('zone');
                $o->zone_image_url = $o->zone_image;
            });
        });
        $secureStudentLink = URL::signedRoute('student.lesson.player', [
            'course' => $course->id,
            'module' => $module->id,
            'lesson' => $lesson->id
        ], now()->addHours(2));

        $allLessons = CourseLesson::whereHas('module', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->select('id', 'title', 'course_module_id')->with('module:id,title')->get();
        return Inertia::render('Admin/Courses/Builder/LessonEditor', [
            'course' => $course,
            'module' => $module,
            'lesson' => $lesson,
            'allLessons' => $allLessons,
            'studentLink' => $secureStudentLink
        ]);
    }

    public function studentPlayer(Request $request, Course $course, CourseModule $module, CourseLesson $lesson)
    {
        // Se o link foi adulterado ou expirou, o middleware 'signed' já bloqueia com erro 403 automaticamente.

        $lesson->load(['questions.options', 'questions.media', 'questions.options.media']);
        $lesson->questions->each(function ($q) {
            $q->background_image = $q->getFirstMediaUrl('background');
            $q->title_image = $q->getFirstMediaUrl('title');
            $q->modal_image = $q->getFirstMediaUrl('modal');
            $q->confirmation_image = $q->getFirstMediaUrl('confirmation');
            $q->confirmation_button_image = $q->getFirstMediaUrl('confirmation_button');
            $q->global_background_image = $q->getFirstMediaUrl('global_background');
            $q->options->each(function ($o) {
                $o->image_url = $o->getFirstMediaUrl('image');
                $o->zone_image = $o->getFirstMediaUrl('zone');
                $o->zone_image_url = $o->zone_image;
            });
        });
        $targetIds = collect();
        foreach ($lesson->questions as $q) {
            if (is_numeric($q->correct_target_lesson_id)) $targetIds->push($q->correct_target_lesson_id);
            if (is_numeric($q->wrong_target_lesson_id)) $targetIds->push($q->wrong_target_lesson_id);

            foreach ($q->options as $opt) {
                if (is_numeric($opt->correct_target_lesson_id)) $targetIds->push($opt->correct_target_lesson_id);
                if (is_numeric($opt->wrong_target_lesson_id)) $targetIds->push($opt->wrong_target_lesson_id);
            }
        }

        // Limpa IDs nulos, duplicados e o ID da própria aula
        $targetIds = $targetIds->filter()->unique()->reject(fn($id) => $id == $lesson->id)->values();

        // 3. Carrega as aulas de destino
        // Use Cache::remember aqui também se quiser máxima performance!
        $branches = Cache::remember(
            "lesson_{$lesson->id}_branches_v2",
            now()->addDays(7),
            function () use ($targetIds) {

                return CourseLesson::whereIn('id', $targetIds)
                    ->with([
                        'questions.options',
                        'questions.media',
                        'questions.options.media'
                    ])
                    ->get()
                    ->map(function ($branchLesson) {

                        $branchLesson->questions->each(function ($q) {

                            $q->background_image = $q->getFirstMediaUrl('background');
                            $q->title_image = $q->getFirstMediaUrl('title');
                            $q->modal_image = $q->getFirstMediaUrl('modal');
                            $q->confirmation_image = $q->getFirstMediaUrl('confirmation');
                            $q->confirmation_button_image = $q->getFirstMediaUrl('confirmation_button');
                            $q->global_background_image = $q->getFirstMediaUrl('global_background');

                            $q->options->each(function ($o) {
                                $o->image_url = $o->getFirstMediaUrl('image');
                                $o->zone_image = $o->getFirstMediaUrl('zone');
                                $o->zone_image_url = $o->zone_image;
                            });
                        });

                        return $branchLesson;
                    })->toArray();
            }
        );

        return Inertia::render('Student/StudentLesson', [
            'course' => $course,
            'module' => $module,
            'lesson' => $lesson,
            'preloadedBranches' => $branches // 🔥 O Pulo do Gato
        ]);
    }

    public function storeInteraction(Request $request, Course $course, CourseModule $module, CourseLesson $lesson)
    {
        $rules = [
            'title' => 'required|string',
            'show_title' => 'boolean',
            'title_color' => 'nullable|string|max:7',
            'title_font_size' => 'nullable|integer|min:8|max:72',
            'title_font_family' => 'nullable|string',
            'background_color' => 'nullable|string|max:7',
            'background_opacity' => 'nullable|numeric|min:0|max:1',
            'title_text_align' => 'nullable|in:left,center,right',
            'title_vertical_align' => 'nullable|in:top,center,bottom',
            'modal_coordinates' => 'nullable|array',
            'modal_background_color' => 'nullable|string|max:20',
            'modal_background_opacity' => 'nullable|numeric|min:0|max:1',
            'question_coordinates' => 'nullable|array',
            'timestamp_seconds' => 'required|numeric|min:0',
            'loop_seconds' => 'nullable|numeric|min:0',
            'loop_start_seconds' => 'nullable|numeric|min:0',
            'loop_end_seconds'   => 'nullable|numeric|min:0',
            'type' => 'required|in:single_choice,multiple_choice,info,drag_and_drop,automatic',
            'correct_action' => 'nullable|string',
            'correct_seek_to' => 'nullable|numeric',
            'wrong_action' => 'nullable|string',
            'wrong_seek_to' => 'nullable|numeric',
            'feedback_text' => 'nullable|string',
            'correct_feedback_image' => 'nullable|image|max:5120',
            'wrong_feedback_image'   => 'nullable|image|max:5120',
            'title_image' => 'nullable|image|max:5120',
            'modal_image' => 'nullable|image|max:5120',
            'background_image' => 'nullable|image|max:5120',
            'confirmation_config' => 'nullable|array',
            'confirmation_image' => 'nullable|image|max:5120',
            'confirmation_button_image' => 'nullable|image|max:5120',
            'global_background_color' => 'nullable|string|max:20',
            'global_background_opacity' => 'nullable|numeric|min:0|max:1',
            'global_background_coordinates' => 'nullable|array',
            'global_background_image' => 'nullable|image|max:5120',
            'options' => 'nullable|array',
            'options.*.is_zone' => 'boolean',
            'options.*.shape' => 'nullable|string',
            'options.*.correct_target_lesson_id' => 'nullable|integer', // 🔥 NOVO
            'options.*.wrong_target_lesson_id' => 'nullable|integer',
            'options.*.image' => 'nullable|image|max:5120',
            'options.*.zone_image' => 'nullable|image|max:5120',
        ];

        $request->validate($rules);

        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $loopSeconds = $request->input('loop_seconds', 0);

        if ($request->filled('loop_start_seconds') && $request->filled('loop_end_seconds')) {
            $start = (float) $request->input('loop_start_seconds');
            $end   = (float) $request->input('loop_end_seconds');
            if ($end > $start) {
                $loopSeconds = round($end - $start, 3);
            }
        }

        $question = $lesson->questions()->create([
            'title' => $request->input('title'),
            'show_title' => $request->input('show_title', true),
            'title_color' => $request->input('title_color', '#000000'),
            'title_font_size' => $request->input('title_font_size', 20),
            'title_font_family' => $request->input('title_font_family', 'Arial, sans-serif'),
            'background_color' => $request->input('background_color', '#ffffff'),
            'background_opacity' => $request->input('background_opacity', 1),
            'title_text_align' => $request->input('title_text_align', 'center'),
            'title_vertical_align' => $request->input('title_vertical_align', 'center'),
            'modal_coordinates' => $request->input('modal_coordinates'),
            'modal_background_color' => $request->input('modal_background_color', '#ffffff'),
            'modal_background_opacity' => $request->input('modal_background_opacity', 1),
            'question_coordinates' => $request->input('question_coordinates'),
            'correct_feedback_config' => $request->input('correct_feedback_config'),
            'wrong_feedback_config'   => $request->input('wrong_feedback_config'),
            'confirmation_config' => $request->input('confirmation_config'),
            'timestamp_seconds' => $request->input('timestamp_seconds'),
            'loop_start_seconds' => $request->input('loop_start_seconds'),
            'loop_end_seconds' => $request->input('loop_end_seconds'),
            'loop_seconds' => $loopSeconds,
            'type' => $request->input('type'),
            'correct_action' => $request->input('correct_action', 'continue'),
            'correct_target_lesson_id' => $request->input('correct_target_lesson_id'), // NOVO
            'correct_seek_to' => $request->input('correct_seek_to'),


            'wrong_action' => $request->input('wrong_action', 'continue'),
            'wrong_target_lesson_id' => $request->input('wrong_target_lesson_id'), // NOVO

            'wrong_seek_to' => $request->input('wrong_seek_to'),
            'correct_feedback' => $request->input('feedback_text'),
            'global_background_color' => $request->input('global_background_color', 'transparent'),
            'global_background_opacity' => $request->input('global_background_opacity', 1),
            'global_background_coordinates' => $request->input('global_background_coordinates'),
            'global_background_text' => $request->input('global_background_text'),
            'global_background_text_color' => $request->input('global_background_text_color', '#ffffff'),
            'global_background_text_font_size' => $request->input('global_background_text_font_size', 24),
            'global_background_text_font_family' => $request->input('global_background_text_font_family', 'Arial, sans-serif'),
            'global_background_text_align' => $request->input('global_background_text_align', 'center'),
            'global_background_vertical_align' => $request->input('global_background_vertical_align', 'center'),
        ]);

        if ($request->hasFile('background_image')) {
            $question->addMediaFromRequest('background_image')->toMediaCollection('background');
        }
        if ($request->hasFile('title_image')) {
            $question->addMediaFromRequest('title_image')->toMediaCollection('title');
        }
        if ($request->hasFile('modal_image')) {
            $question->addMediaFromRequest('modal_image')->toMediaCollection('modal');
        }
        if ($request->hasFile('correct_feedback_image')) {
            $question->addMediaFromRequest('correct_feedback_image')->toMediaCollection('feedback_correct');
        }
        if ($request->hasFile('wrong_feedback_image')) {
            $question->addMediaFromRequest('wrong_feedback_image')->toMediaCollection('feedback_wrong');
        }
        if ($request->hasFile('confirmation_image')) {
            $question->addMediaFromRequest('confirmation_image')->toMediaCollection('confirmation');
        }
        if ($request->hasFile('confirmation_button_image')) {
            $question->addMediaFromRequest('confirmation_button_image')->toMediaCollection('confirmation_button');
        }
        if ($request->hasFile('global_background_image')) {
            $question->addMediaFromRequest('global_background_image')->toMediaCollection('global_background');
        }

        if ($request->has('options') && is_array($request->options) && !in_array($request->type, ['automatic'])) {
            foreach ($request->options as $idx => $opt) {
                $option = $question->options()->create([
                    'text' => $opt['text'] ?? '',
                    'is_correct' => $opt['is_correct'] ?? false,
                    'is_zone' => $opt['is_zone'] ?? false,
                    'shape' => $opt['shape'] ?? 'rectangle',
                    'group_id' => $opt['group_id'] ?? 0,
                    'text_color' => $opt['text_color'] ?? '#000000',
                    'text_font_size' => $opt['text_font_size'] ?? 14,
                    'text_font_family' => $opt['text_font_family'] ?? 'Arial, sans-serif',
                    'show_text' => $opt['show_text'] ?? true,
                    'coordinates' => $opt['coordinates'] ?? null,
                    'delay_seconds' => $opt['delay_seconds'] ?? 0,
                    'correct_action' => $opt['correct_action'] ?? null,
                    'correct_target_lesson_id' => $opt['correct_target_lesson_id'] ?? null, // NOVO
                    'correct_seek_to' => $opt['correct_seek_to'] ?? null,

                    'wrong_action' => $opt['wrong_action'] ?? null,
                    'wrong_target_lesson_id' => $opt['wrong_target_lesson_id'] ?? null, // NOVO
                    'wrong_seek_to' => $opt['wrong_seek_to'] ?? null,
                    'background_color' => $opt['background_color'] ?? 'transparent',
                    'background_opacity' => $opt['background_opacity'] ?? 1,
                    'text_align' => $opt['text_align'] ?? 'center',
                    'vertical_align' => $opt['vertical_align'] ?? 'center',
                    'feedback_text' => $opt['feedback_text'] ?? null,
                ]);
                if ($request->hasFile("options.$idx.image")) {
                    $option->addMediaFromRequest("options.$idx.image")->toMediaCollection('image');
                }
                if ($request->hasFile("options.$idx.zone_image")) {
                    $option->addMediaFromRequest("options.$idx.zone_image")->toMediaCollection('zone');
                }
            }
        }
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Interação salva com sucesso!');
    }

    public function updateInteraction(Request $request, Course $course, CourseModule $module, CourseLesson $lesson, LessonQuestion $question)
    {
        $rules = [
            'title' => 'required|string',
            'type' => 'required|in:single_choice,multiple_choice,info,drag_and_drop,automatic',
            'options' => 'nullable|array',
            'options.*.is_zone' => 'boolean',
            'options.*.shape' => 'nullable|string',
            'options.*.correct_target_lesson_id' => 'nullable|integer',
            'options.*.wrong_target_lesson_id' => 'nullable|integer',
            'options.*.correct_seek_to' => 'nullable|numeric',
            'options.*.wrong_seek_to' => 'nullable|numeric',
            'options.*.correct_action' => 'nullable|string',
            'options.*.wrong_action' => 'nullable|string',
        ];

        $loopSeconds = $request->input('loop_seconds', 0);

        app(CoursePublicationService::class)->markSourceAsEditing($course);
        if ($request->filled('loop_start_seconds') && $request->filled('loop_end_seconds')) {
            $start = (float) $request->input('loop_start_seconds');
            $end   = (float) $request->input('loop_end_seconds');
            if ($end > $start) {
                $loopSeconds = round($end - $start, 3);
            }
        }

        $question->update([
            'title' => $request->input('title'),
            'show_title' => $request->input('show_title', true),
            'title_color' => $request->input('title_color', '#000000'),
            'title_font_size' => $request->input('title_font_size', 20),
            'title_font_family' => $request->input('title_font_family', 'Arial, sans-serif'),
            'background_color' => $request->input('background_color', '#ffffff'),
            'background_opacity' => $request->input('background_opacity', 1),
            'title_text_align' => $request->input('title_text_align', 'center'),
            'title_vertical_align' => $request->input('title_vertical_align', 'center'),
            'modal_coordinates' => $request->input('modal_coordinates'),
            'modal_background_color' => $request->input('modal_background_color', '#ffffff'),
            'modal_background_opacity' => $request->input('modal_background_opacity', 1),
            'question_coordinates' => $request->input('question_coordinates'),
            'timestamp_seconds' => $request->input('timestamp_seconds'),
            'loop_seconds' => $loopSeconds,
            'loop_start_seconds' => $request->input('loop_start_seconds'),
            'loop_end_seconds' => $request->input('loop_end_seconds'),
            'type' => $request->input('type'),
            'correct_action' => $request->input('correct_action', 'continue'),
            'correct_target_lesson_id' => $request->input('correct_target_lesson_id'), // NOVO
            'correct_seek_to' => $request->input('correct_seek_to'),

            'wrong_action' => $request->input('wrong_action', 'continue'),
            'wrong_target_lesson_id' => $request->input('wrong_target_lesson_id'), // NOVO
            'wrong_seek_to' => $request->input('wrong_seek_to'),
            'correct_feedback' => $request->input('feedback_text'),
            'correct_feedback_config' => $request->input('correct_feedback_config'),
            'wrong_feedback_config'   => $request->input('wrong_feedback_config'),
            'confirmation_config' => $request->input('confirmation_config'),
            'global_background_color' => $request->input('global_background_color', 'transparent'),
            'global_background_opacity' => $request->input('global_background_opacity', 1),
            'global_background_coordinates' => $request->input('global_background_coordinates'),
            'global_background_text' => $request->input('global_background_text'),
            'global_background_text_color' => $request->input('global_background_text_color', '#ffffff'),
            'global_background_text_font_size' => $request->input('global_background_text_font_size', 24),
            'global_background_text_font_family' => $request->input('global_background_text_font_family', 'Arial, sans-serif'),
            'global_background_text_align' => $request->input('global_background_text_align', 'center'),
            'global_background_vertical_align' => $request->input('global_background_vertical_align', 'center'),
        ]);

        if ($request->boolean('remove_background_image')) $question->clearMediaCollection('background');
        if ($request->hasFile('background_image')) {
            $question->clearMediaCollection('background');
            $question->addMediaFromRequest('background_image')->toMediaCollection('background');
        }

        if ($request->boolean('remove_title_image')) $question->clearMediaCollection('title');
        if ($request->hasFile('title_image')) {
            $question->clearMediaCollection('title');
            $question->addMediaFromRequest('title_image')->toMediaCollection('title');
        }

        if ($request->boolean('remove_modal_image')) $question->clearMediaCollection('modal');
        if ($request->hasFile('modal_image')) {
            $question->clearMediaCollection('modal');
            $question->addMediaFromRequest('modal_image')->toMediaCollection('modal');
        }

        if ($request->boolean('remove_correct_feedback_image')) $question->clearMediaCollection('feedback_correct');
        if ($request->hasFile('correct_feedback_image')) {
            $question->clearMediaCollection('feedback_correct');
            $question->addMediaFromRequest('correct_feedback_image')->toMediaCollection('feedback_correct');
        }

        if ($request->boolean('remove_wrong_feedback_image')) $question->clearMediaCollection('feedback_wrong');
        if ($request->hasFile('wrong_feedback_image')) {
            $question->clearMediaCollection('feedback_wrong');
            $question->addMediaFromRequest('wrong_feedback_image')->toMediaCollection('feedback_wrong');
        }

        if ($request->boolean('remove_confirmation_image')) $question->clearMediaCollection('confirmation');
        if ($request->hasFile('confirmation_image')) {
            $question->clearMediaCollection('confirmation');
            $question->addMediaFromRequest('confirmation_image')->toMediaCollection('confirmation');
        }

        if ($request->boolean('remove_confirmation_button_image')) $question->clearMediaCollection('confirmation_button');
        if ($request->hasFile('confirmation_button_image')) {
            $question->clearMediaCollection('confirmation_button');
            $question->addMediaFromRequest('confirmation_button_image')->toMediaCollection('confirmation_button');
        }

        if ($request->boolean('remove_global_background_image')) $question->clearMediaCollection('global_background');
        if ($request->hasFile('global_background_image')) {
            $question->clearMediaCollection('global_background');
            $question->addMediaFromRequest('global_background_image')->toMediaCollection('global_background');
        }

        $incomingOptionIds = collect($request->options)->pluck('id')->filter()->toArray();

        if (empty($incomingOptionIds)) {
            $question->options()->delete();
        } else {
            $question->options()->whereNotIn('id', $incomingOptionIds)->delete();
        }

        if ($request->has('options') && is_array($request->options) && !in_array($request->type, ['automatic'])) {
            foreach ($request->options as $idx => $opt) {

                $option = $question->options()->updateOrCreate(
                    ['id' => $opt['id'] ?? null],
                    [
                        'text' => $opt['text'] ?? '',
                        'is_correct' => $opt['is_correct'] ?? false,
                        'group_id' => $opt['group_id'] ?? 0,
                        'is_zone' => $opt['is_zone'] ?? false,
                        'shape' => $opt['shape'] ?? 'rectangle',
                        'text_color' => $opt['text_color'] ?? '#000000',
                        'text_font_size' => $opt['text_font_size'] ?? 14,
                        'text_font_family' => $opt['text_font_family'] ?? 'Arial, sans-serif',
                        'show_text' => $opt['show_text'] ?? true,
                        'coordinates' => $opt['coordinates'] ?? null,
                        'delay_seconds' => $opt['delay_seconds'] ?? 0,
                        'correct_action' => $opt['correct_action'] ?? null,
                        'correct_target_lesson_id' => $opt['correct_target_lesson_id'] ?? null, // NOVO
                        'correct_seek_to' => $opt['correct_seek_to'] ?? null,

                        'wrong_action' => $opt['wrong_action'] ?? null,
                        'wrong_target_lesson_id' => $opt['wrong_target_lesson_id'] ?? null, // NOVO
                        'wrong_seek_to' => $opt['wrong_seek_to'] ?? null,
                        'background_color' => $opt['background_color'] ?? 'transparent',
                        'background_opacity' => $opt['background_opacity'] ?? 1,
                        'text_align' => $opt['text_align'] ?? 'center',
                        'vertical_align' => $opt['vertical_align'] ?? 'center',
                        'feedback_text' => $opt['feedback_text'] ?? null,
                    ]
                );

                if (!empty($opt['remove_image'])) $option->clearMediaCollection('image');
                if ($request->hasFile("options.$idx.image")) {
                    $option->clearMediaCollection('image');
                    $option->addMediaFromRequest("options.$idx.image")->toMediaCollection('image');
                }

                if (!empty($opt['remove_zone_image'])) $option->clearMediaCollection('zone');
                if ($request->hasFile("options.$idx.zone_image")) {
                    $option->clearMediaCollection('zone');
                    $option->addMediaFromRequest("options.$idx.zone_image")->toMediaCollection('zone');
                }
            }
        }
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Interação atualizada!');
    }

    public function destroyInteraction(Course $course, CourseModule $module, CourseLesson $lesson, LessonQuestion $question)
    {
        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $question->delete();
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Interação removida.');
    }

    public function updateModule(Request $request, Course $course, CourseModule $module)
    {
        $validated = $request->validate(['title' => 'required|string|max:255']);
        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $module->update($validated);
        return back()->with('success', 'Módulo atualizado.');
    }

    public function destroyModule(Course $course, CourseModule $module)
    {
        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $module->delete();
        return back()->with('success', 'Módulo removido.');
    }

    public function updateLesson(Request $request, Course $course, CourseModule $module, CourseLesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'video_url' => 'required|url'
        ]);

        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $lesson->update([
            'title' => $validated['title'],
            'video_url' => $validated['video_url'],
        ]);
        // 🔥 Limpa o cache da aula atualizada
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Aula atualizada.');
    }

    public function destroyLesson(Course $course, CourseModule $module, CourseLesson $lesson)
    {
        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $lesson->delete();
        return back()->with('success', 'Aula removida.');
    }

    public function uploadQuestionImage(
        Request $request,
        Course $course,
        CourseModule $module,
        CourseLesson $lesson,
        LessonQuestion $question
    ) {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB
            'type' => 'required|in:background,title,modal',
        ]);

        app(CoursePublicationService::class)->markSourceAsEditing($course);

        $collection = match ($request->type) {
            'background' => 'background_image',
            'title' => 'title_image',
            'modal' => 'modal_image',
        };

        $question
            ->clearMediaCollection($collection)
            ->addMediaFromRequest('image')
            ->toMediaCollection($collection);
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Imagem enviada com sucesso.');
    }

    public function uploadOptionImage(
        Request $request,
        Course $course,
        CourseModule $module,
        CourseLesson $lesson,
        QuestionOption $option
    ) {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        app(CoursePublicationService::class)->markSourceAsEditing($course);

        $option
            ->clearMediaCollection('option_image')
            ->addMediaFromRequest('image')
            ->toMediaCollection('option_image');
        Cache::forget("lesson_{$lesson->id}_branch_data_v1");
        return back()->with('success', 'Imagem da opção enviada.');
    }

    public function getLessonData(Course $course, CourseLesson $lesson)
    {
        // A chave de cache deve invalidar se a aula for atualizada
        $cacheKey = "lesson_{$lesson->id}_branch_data_v1";

        $lessonData = Cache::remember($cacheKey, now()->addDays(7), function () use ($lesson) {

            // 🔥 A MÁGICA 1: Eager Load Profundo (Mata o N+1 da Spatie)
            // Adicionamos 'media' e 'options.media' para trazer as imagens na mesma viagem
            $lesson->load([
                'questions.options',
                'questions.media',         // Carrega todas as imagens das perguntas de uma vez
                'questions.options.media'  // Carrega todas as imagens das opções de uma vez
            ]);

            $lesson->questions->each(function ($q) {
                // Como a relação 'media' já está carregada, o getFirstMediaUrl
                // agora lê direto da memória RAM, sem fazer queries!
                $q->background_image = $q->getFirstMediaUrl('background');
                $q->title_image = $q->getFirstMediaUrl('title');
                $q->modal_image = $q->getFirstMediaUrl('modal');
                $q->confirmation_image = $q->getFirstMediaUrl('confirmation');
                $q->confirmation_button_image = $q->getFirstMediaUrl('confirmation_button');
                $q->global_background_image = $q->getFirstMediaUrl('global_background');

                $q->options->each(function ($o) {
                    $o->image_url = $o->getFirstMediaUrl('image');
                    $o->zone_image = $o->getFirstMediaUrl('zone');
                    $o->zone_image_url = $o->zone_image; // Mantendo a sua retrocompatibilidade
                });
            });

            return $lesson->toArray();
        });

        // Retornamos JSON puro para o Vue puxar via Axios instantaneamente!
        return response()->json([
            'lesson' => $lessonData
        ]);
    }
}
