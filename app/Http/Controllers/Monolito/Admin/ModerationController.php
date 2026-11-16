<?php

namespace App\Http\Controllers\Monolito\Admin;

use App\Http\Controllers\BaseController;
use Inertia\Inertia;
use App\Models\CensoredWord;
use Illuminate\Http\Request;
use App\Models\ModerationCase;
use Spatie\QueryBuilder\QueryBuilder;

class ModerationController extends BaseController
{
    /**
     * Exibe a fila de moderação com filtros e ordenação.
     */
    public function index()
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Moderação', '') // Link nulo para item não clicável
            ->add(__('Fila de Moderação'), '');

        $perPage = request()->input('per_page', 15);

        $moderationCases = QueryBuilder::for(ModerationCase::class)
            ->allowedFilters(['status', 'comment.user.name'])
            ->defaultSort('-created_at')
            ->allowedSorts(['created_at', 'moderated_at'])
            ->with([
                'comment' => fn($q) => $q->with('user:id,name'),
                'comment.commentable',
                'moderator:id,name'
            ])
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Moderation/Index', [
            'moderationCases' => $moderationCases,
            'queryParams' => request()->query() ?: null,
        ]);
    }

    /**
     * Mostra os detalhes de um caso de moderação específico.
     */
    public function show(ModerationCase $moderationCase)
    {
        // Carrega todas as relações necessárias para dar contexto ao moderador
        $moderationCase->load([
            'comment' => fn($q) => $q->with('user:id,name'),
            'comment.commentable', // O Post ou Produto que foi comentado
            'moderator:id,name'
        ]);
        if ($moderationCase->comment->commentable) {
            $moderationCase->comment->commentable->type = $moderationCase->comment->commentable->getMorphClass();
        }
        return Inertia::render('Admin/Moderation/Show', [
            'moderationCase' => $moderationCase,
            'censoredWords' => CensoredWord::pluck('word')->toArray(),
        ]);
    }

    /**
     * Processa a decisão de um moderador sobre um caso.
     */
    public function update(Request $request, ModerationCase $moderationCase)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,delete',
            'notes' => 'nullable|string|max:5000',
        ]);

        if ($validated['action'] === 'approve') {
            $moderationCase->update([
                'status' => 'approved',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'notes' => $validated['notes'],
            ]);
        } elseif ($validated['action'] === 'delete') {
            // Apaga o comentário associado
            $moderationCase->comment->delete();

            $moderationCase->update([
                'status' => 'comment_deleted',
                'moderator_id' => auth()->id(),
                'moderated_at' => now(),
                'notes' => $validated['notes'],
            ]);
        }

        return redirect()->route('admin.moderation.index')->with('success', 'Ação de moderação concluída.');
    }
}
