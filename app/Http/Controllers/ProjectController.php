<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\QueryService;
use App\Http\Resources\ProjectResource;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\SubmitProjectRequest;
use App\Http\Requests\RejectProjectRequest;
use App\Services\CoursePublicationService;
use Illuminate\Support\Facades\DB;

class ProjectController extends BaseController
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Projects', route('projects.index'));
        $company = app('current_company');
        $base = $company->projects();

        $projects = QueryService::query(
            // A query base já filtra pelo papel 'System Developer'
            $base,
            Project::ALLOWEDFILTERS(),
            [],
            Project::ALLOWEDSORTS()
        )
            ->with(['course:id,title,updated_at', 'activePublication', 'publications'])
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
        ]);
    }



    public function create()
    {
        $this->authorize('create', Project::class);

        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'))
            ->add('Projetos', route('projects.index'))
            ->add('Novo Projeto');

        // Apenas renderiza a view do formulário de criação.
        return Inertia::render('Projects/Create');
    }

    public function store(StoreProjectRequest $request)
    {
        $company = app('current_company');

        // Cria o projeto associado à empresa e ao utilizador logado
        $company->projects()->create([
            'user_id' => auth()->id(),
            'title' => $request->input('title'),
            'type' => $request->input('type'),
        ]);

        return redirect()->route('projects.index')->with('message', 'Projeto criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function edit(Project $project)
    {
        // Usa a ProjectPolicy para verificar se o utilizador pode ver esta página
        $this->authorize('update', $project);

        return Inertia::render('Projects/Edit', [
            'project' => (ProjectResource::make($project->load('videos.interactions'))),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        // Usa a ProjectPolicy para verificar se o utilizador pode executar esta ação
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()->route('projects.index')->with('message', 'Projeto atualizado com sucesso!');
    }

    public function destroy(Project $project)
    {
        // Usa a ProjectPolicy para verificar se o utilizador pode executar esta ação
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('message', 'Projeto excluído com sucesso!');
    }

    public function trashed()
    {
        $this->authorize('viewAny', Project::class);

        $company = app('current_company');

        // Busca apenas os projetos que foram "soft-deleted"
        $trashedProjects = $company->projects()->onlyTrashed()->latest()->paginate(10);

        return Inertia::render('Projects/Trashed', [
            'projects' => ProjectResource::collection($trashedProjects),
        ]);
    }

    public function restore(Project $project)
    {
        $this->authorize('update', $project); // Reutilizamos a permissão de update
        $project->restore();
        return back()->with('message', 'Projeto reativado com sucesso!');
    }

    public function submitConfirmation(Project $project)
    {
        $this->authorize('submit', $project);

        return Inertia::render('Projects/Submit', [
            'project' => ProjectResource::make($project),
        ]);
    }

    public function submitIndex()
    {
        $this->authorize('viewAny', Project::class);

        $projects = app('current_company')->projects()
            ->where('status', Project::STATUS_DRAFT)
            ->latest()
            ->paginate(15);

        return Inertia::render('Projects/SubmitIndex', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function submit(SubmitProjectRequest $request, Project $project)
    {
        DB::transaction(function () use ($project, $request): void {
            $lockedProject = Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedProject->status === Project::STATUS_DRAFT, 422, 'Project is not eligible for submission.');

            $from = $lockedProject->status;
            $lockedProject->update(['status' => Project::STATUS_PENDING_APPROVAL]);

            activity('project_status')
                ->event('submitted')
                ->performedOn($lockedProject)
                ->causedBy($request->user())
                ->withProperties([
                    'from' => $from,
                    'to' => Project::STATUS_PENDING_APPROVAL,
                    'company_id' => $lockedProject->company_id,
                ])
                ->log('Project enviado para aprovação');
        });

        return redirect()->route('projects.index')->with('message', 'Projeto enviado para aprovação.');
    }

    public function pending(Request $request)
    {
        $this->authorize('viewPending', Project::class);

        $projects = ($request->user()->hasRole('Master') ? Project::query() : app('current_company')->projects())
            ->where('status', Project::STATUS_PENDING_APPROVAL)
            ->with('course:id,title')
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return Inertia::render('Projects/Pending', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    public function approve(Request $request, Project $project)
    {
        $this->authorize('approve', $project);

        $this->transitionDecision($project, $request->user(), Project::STATUS_APPROVED);

        return back()->with('message', 'Projeto aprovado.');
    }

    public function reject(RejectProjectRequest $request, Project $project)
    {
        $feedback = trim($request->validated('feedback'));
        abort_if($feedback === '', 422, 'Feedback is required.');

        $this->transitionDecision($project, $request->user(), Project::STATUS_REJECTED, $feedback);

        return back()->with('message', 'Projeto reprovado com justificativa.');
    }

    public function publish(Request $request, Project $project, CoursePublicationService $publicationService)
    {
        $this->authorize('publish', $project);
        $publicationService->publish($project, $request->user());

        return back()->with('message', 'Projeto publicado.');
    }

    public function revoke(Request $request, Project $project, CoursePublicationService $publicationService)
    {
        $this->authorize('revoke', $project);
        $publicationService->revoke($project, $request->user());

        return back()->with('message', 'Publicação revogada.');
    }

    private function transitionDecision(Project $project, $actor, string $to, ?string $feedback = null): void
    {
        DB::transaction(function () use ($project, $actor, $to, $feedback): void {
            $lockedProject = Project::query()->whereKey($project->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedProject->status === Project::STATUS_PENDING_APPROVAL, 422, 'Project is not eligible for this decision.');

            $from = $lockedProject->status;
            $lockedProject->update(['status' => $to]);

            activity('project_status')
                ->event($to === Project::STATUS_APPROVED ? 'approved' : 'rejected')
                ->performedOn($lockedProject)
                ->causedBy($actor)
                ->withProperties(array_filter([
                    'from' => $from,
                    'to' => $to,
                    'company_id' => $lockedProject->company_id,
                    'course_id' => $lockedProject->course_id,
                    'feedback' => $feedback,
                ], fn ($value) => $value !== null))
                ->log($to === Project::STATUS_APPROVED ? 'Project aprovado' : 'Project reprovado');
        });
    }
}
