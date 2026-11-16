<?php

namespace App\Http\Resources;

use App\Traits\FormatsDates;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public static $wrap = null;
    use FormatsDates;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activePublication = $this->relationLoaded('activePublication') ? $this->activePublication : null;
        $latestPublication = $this->relationLoaded('publications')
            ? $this->publications->sortByDesc('revision_number')->first()
            : null;
        $hasActivePublication = $activePublication !== null;
        $canPublish = $request->user()?->can('publish', $this->resource) ?? false;
        $canRevoke = $request->user()?->can('revoke', $this->resource) ?? false;
        $republishReady = $hasActivePublication
            && $this->status === \App\Models\Project::STATUS_APPROVED
            && $this->relationLoaded('course')
            && $this->course?->updated_at?->gt($activePublication->published_at);

        $publicationLabel = $hasActivePublication
            ? ($republishReady ? 'Nova revisão aprovada disponível para republicar' : 'Publicado')
            : ($latestPublication?->state === \App\Models\CoursePublication::REVOKED ? 'Publicação revogada' : 'Não publicado');
        $rejection = $this->relationLoaded('approvalHistory')
            ? $this->approvalHistory->firstWhere('event', 'rejected')
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'rejection_feedback' => data_get($rejection?->properties, 'feedback'),
            // 'created_at' => $this->created_at->toIso8601String(),
            // 'updated_at' => $this->updated_at->toIso8601String(),

            // 'deleted_at' => $this->deleted_at?->toIso8601String(),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
            'deleted_at' => $this->formatDate($this->deleted_at), // Adiciona a data de exclusão

            'user' => UserResource::make($this->whenLoaded('user')),
            'videos' => VideoResource::collection($this->whenLoaded('videos')),
            'course' => $this->when($this->relationLoaded('course'), fn () => $this->course ? [
                'id' => $this->course->id,
                'title' => $this->course->title,
            ] : null),
            'publication' => [
                'state' => $publicationLabel,
                'active' => $hasActivePublication,
                'can_publish' => $canPublish,
                'can_revoke' => $canRevoke,
                'republish_ready' => $republishReady,
                ...($canPublish && $hasActivePublication && $this->token ? [
                    'public_url' => route('publications.show', ['token' => $this->token]),
                ] : []),
            ],

        ];
    }
}
