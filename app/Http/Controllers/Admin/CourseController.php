<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use App\Models\Course;
use App\Models\Project;
use App\Http\Resources\ProjectResource;
use App\Models\Category;
use App\Services\BreadcrumbsManager;
use App\Services\CoursePublicationService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class CourseController extends BaseController
{
    public function __construct(BreadcrumbsManager $breadcrumbsManager)
    {
        parent::__construct($breadcrumbsManager);

        $this->authorizeResource(Course::class, 'course');
    }

    public function index(Request $request)
    {
        $courses = QueryBuilder::for(Course::class)
            ->where('is_publication_snapshot', false)
            ->allowedFilters(['title']) // Filtro por título
            ->allowedSorts(['title', 'price', 'created_at', 'is_published']) // Ordenação
            ->withCount(['modules', 'lessons'])
            ->with([
                'project:id,company_id,course_id,user_id,title,type,status,token',
                'project.approvalHistory',
                'project.course:id,title,updated_at',
                'project.activePublication',
                'project.publications',
            ])
            ->defaultSort('-created_at')
            ->when(! Auth::user()->hasRole('Master'), fn ($query) => $query->where('company_id', app('current_company')->id))
            ->paginate(10)
            ->withQueryString()
            ->through(function (Course $course) use ($request): array {
                return [
                    ...$course->toArray(),
                    'project' => $course->project
                        ? ProjectResource::make($course->project)->resolve($request)
                        : null,
                ];
            });

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            // CRÍTICO: Passar os filtros de volta para o frontend
            'filters' => $request->only(['filter', 'sort']),
        ]);
    }

    public function create()
    {
        $categories = Category::courses()->get()->toTree();

        return Inertia::render('Admin/Courses/Form', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'headline' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'level' => 'required|string',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'thumbnail' => 'nullable|image|max:2048',
            'categories' => 'array|exists:categories,id', // Validação
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = Auth::user()->id;
        $validated['company_id'] = app('current_company')->id;

        $course = Course::create($validated);

        // Sincronizar Categorias
        if (!empty($request->categories)) {
            $course->categories()->sync($request->categories);
        }

        // Upload da Capa
        if ($request->hasFile('thumbnail')) {
            $course->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        return to_route('admin.courses.builder.index', $course->id)
            ->with('success', 'Projeto criado! Comece adicionando módulos.');
    }

    public function edit(Course $course)
    {
        // Carregar categorias do curso
        $course->load('categories');

        return Inertia::render('Admin/Courses/Form', [
            'course' => $course,
            'categories' => Category::courses()->get()->toTree(),
            'selectedCategories' => $course->categories->pluck('id') // IDs para o checkbox
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'headline' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'level' => 'required|string',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'thumbnail' => 'nullable',
            'categories' => 'array|exists:categories,id', // Validação
        ]);

        if ($course->title !== $request->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        app(CoursePublicationService::class)->markSourceAsEditing($course);
        $course->update($validated);


        // Sincronizar Categorias
        if (isset($request->categories)) {
            $course->categories()->sync($request->categories);
        }

        if ($request->hasFile('thumbnail')) {
            $course->clearMediaCollection('thumbnail');
            $course->addMediaFromRequest('thumbnail')->toMediaCollection('thumbnail');
        }

        return to_route('admin.courses.index')->with('success', 'Projeto atualizado.');
    }
    public function destroy(Course $course)
    {
        $course->delete();
        return back()->with('success', 'Projeto excluído.');
    }

    public function submit(Course $course)
    {
        $this->authorize('submit', $course);

        DB::transaction(function () use ($course): void {
            $lockedCourse = Course::query()
                ->whereKey($course->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedCourse->company_id !== null, 422, 'Legacy courses cannot be submitted.');

            $project = Project::query()
                ->where('course_id', $lockedCourse->id)
                ->lockForUpdate()
                ->first();

            if ($project) {
                abort_unless(
                    (int) $project->company_id === (int) $lockedCourse->company_id
                    && (int) $project->user_id === (int) $lockedCourse->user_id,
                    409,
                    'The workflow project is inconsistent with its course.'
                );

                if ($project->status === Project::STATUS_PENDING_APPROVAL) {
                    return;
                }

                abort_unless(in_array($project->status, [Project::STATUS_DRAFT, Project::STATUS_REJECTED], true), 422, 'Course is not eligible for submission.');

                $from = $project->status;
                $project->update(['status' => Project::STATUS_PENDING_APPROVAL]);
            } else {
                $project = Project::create([
                    'course_id' => $lockedCourse->id,
                    'company_id' => $lockedCourse->company_id,
                    'user_id' => $lockedCourse->user_id,
                    'title' => $lockedCourse->title,
                    'type' => 'course',
                    'status' => Project::STATUS_PENDING_APPROVAL,
                ]);
                $from = Project::STATUS_DRAFT;
            }

            activity('project_status')
                ->event('submitted')
                ->performedOn($project)
                ->causedBy(Auth::user())
                ->withProperties([
                    'from' => $from,
                    'to' => Project::STATUS_PENDING_APPROVAL,
                    'company_id' => $project->company_id,
                    'course_id' => $lockedCourse->id,
                ])
                ->log('Project enviado para aprovação');
        });

        return to_route('admin.courses.index')->with('success', 'Projeto enviado para aprovação.');
    }
}
