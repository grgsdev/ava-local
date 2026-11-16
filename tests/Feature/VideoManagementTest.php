<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Video;
use App\Models\Company;
use App\Models\Project;
use App\Models\Interaction;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VideoManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_add_a_video_to_a_project(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);

        $this->app->singleton('current_company', fn() => $company);

        $videoData = [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Vídeo de Teste',
        ];

        // 2. Act
        $response = $this->actingAs($user)
            // Adicionamos manualmente os headers para simular um navegador
            ->withHeaders([
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->post(route('projects.videos.store', $project), $videoData);

        // 3. Assert
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertDatabaseHas('videos', [
            'project_id' => $project->id,
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Vídeo de Teste',
        ]);
    }

    #[Test]
    public function an_authorized_user_can_delete_a_video_from_a_project(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);
        // Cria uma interação associada para garantir que a exclusão em cascata funciona
        $interaction = Interaction::factory()->create(['video_id' => $video->id]);

        $this->app->singleton('current_company', fn() => $company);

        // Garante que os dados existem antes da ação
        $this->assertDatabaseHas('videos', ['id' => $video->id]);
        $this->assertDatabaseHas('interactions', ['id' => $interaction->id]);

        // 2. Act
        $response = $this->actingAs($user)
            ->delete(route('videos.destroy', $video));

        // 3. Assert
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
        // Verifica se a interação associada também foi apagada (graças ao onDelete('cascade') da migração)
        $this->assertDatabaseMissing('interactions', ['id' => $interaction->id]);
    }
}
