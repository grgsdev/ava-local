<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class VideoController extends BaseController
{
    /**
     * Armazena um novo vídeo associado a um projeto.
     */
    public function store(Request $request, Project $project)
    {
        // 1. Autorização: Garante que o utilizador autenticado pode atualizar este projeto.
        // Reutilizamos a nossa ProjectPolicy, que verifica se o projeto pertence à empresa do utilizador.
        $this->authorize('update', $project);

        // 2. Validação: Garante que os dados enviados são válidos.
        $validated = $request->validate([
            'url' => 'required|url',
            'title' => 'nullable|string|max:255',
        ]);

        // 3. Criação: Cria o novo registo de vídeo associado ao projeto.
        $project->videos()->create($validated);

        // 4. Redirecionamento: Volta para a página de edição do projeto.
        return Redirect::route('projects.edit', $project)
            ->with('message', 'Vídeo adicionado com sucesso!');
    }

    /**
     * Remove um vídeo de um projeto.
     */
    public function destroy(Video $video)
    {
        // 1. Autorização: Garante que o utilizador pode editar o projeto ao qual este vídeo pertence
        $this->authorize('update', $video->project);

        // Guarda o ID do projeto antes de apagar, para o redirecionamento
        $projectId = $video->project_id;

        // 2. Exclusão: Apaga o vídeo. As interações associadas serão apagadas em cascata.
        $video->delete();

        // 3. Redirecionamento
        return Redirect::route('projects.edit', $projectId)
            ->with('message', 'Vídeo removido com sucesso!');
    }
}
