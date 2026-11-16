<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Interaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\StoreInteractionRequest;

class InteractionController extends BaseController
{
    /**
     * Armazena uma nova interação associada a um vídeo.
     */
    public function store(StoreInteractionRequest $request, Video $video)
    {
        // 1. Autorização: Garante que o utilizador pode editar o projeto ao qual este vídeo pertence.
        $this->authorize('update', $video->project);
        $request->validate([
            'graphic' => 'nullable|file|mimes:jpg,jpeg,png,gif|max:2048', // 2MB Max
        ]);

        $validated = $request->validated();

        // 2. Cria a interação com os dados principais
        $interaction = $video->interactions()->create([
            'type' => $validated['type'],
            'timestamp' => $validated['timestamp'],
            'data' => $validated['payload'],
        ]);

        if ($request->hasFile('graphic')) {
            $interaction->addMediaFromRequest('graphic')
                ->toMediaCollection('interaction_graphics');
        }

        // (Opcional, mas recomendado) Atualiza o payload com a URL da imagem para o frontend
        if ($interaction->hasMedia('interaction_graphics')) {
            $payload = $interaction->data; // 'data' é a coluna na base de dados

            // Adiciona as URLs da imagem original e do thumbnail
            $payload['image_url'] = $interaction->getFirstMediaUrl('interaction_graphics');
            $payload['thumbnail_url'] = $interaction->getFirstMediaUrl('interaction_graphics', 'thumbnail');

            $interaction->data = $payload;
            $interaction->save();
        }


        // 3. Redirecionamento: Volta para a página de edição do projeto.
        return Redirect::route('projects.edit', $video->project_id)
            ->with('message', 'Interação adicionada com sucesso!');
    }

    /**
     * Atualiza uma interação existente.
     */
    public function update(StoreInteractionRequest $request, Interaction $interaction)
    {
        // 1. Autorização: Garante que o utilizador pode editar o projeto
        $this->authorize('update', $interaction->video->project);

        // 2. Atualização: Usa os dados validados para atualizar a interação
        $validated = $request->validated();
        $interaction->update([
            'type' => $validated['type'],
            'timestamp' => $validated['timestamp'],
            'data' => $validated['payload'],
        ]);

        // 3. Redirecionamento
        return Redirect::route('projects.edit', $interaction->video->project_id)
            ->with('message', 'Interação atualizada com sucesso!');
    }

    /**
     * Apaga uma interação.
     */
    public function destroy(Interaction $interaction)
    {
        // 1. Autorização
        $this->authorize('update', $interaction->video->project);

        // Guarda o ID do projeto antes de apagar, para o redirecionamento
        $projectId = $interaction->video->project_id;

        // 2. Exclusão
        $interaction->delete();

        // 3. Redirecionamento
        return Redirect::route('projects.edit', $projectId)
            ->with('message', 'Interação removida com sucesso!');
    }
}
