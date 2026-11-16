<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\Interaction;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_add_an_interaction_to_a_video(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'single_choice',
            'timestamp' => 15.5,
            'payload' => [
                'question' => 'Qual é a cor do céu?',
                'answers' => [
                    ['text' => 'Azul', 'is_correct' => true, 'action' => 'continue', 'target' => null],
                    ['text' => 'Verde', 'is_correct' => false, 'action' => 'continue', 'target' => null],
                ]
            ]
        ];

        // 2. Act
        $response = $this->actingAs($user)
            ->post(route('videos.interactions.store', $video), $interactionData);

        // 3. Assert
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertDatabaseHas('interactions', [
            'video_id' => $video->id,
            'type' => 'single_choice',
            'timestamp' => 15.5,
        ]);
    }

    #[Test]
    public function an_authorized_user_can_update_an_interaction(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);
        $interaction = Interaction::factory()->create(['video_id' => $video->id]);

        $this->app->singleton('current_company', fn() => $company);

        $updatedData = [
            'type' => 'single_choice',
            'timestamp' => 20.0,
            'payload' => [
                'question' => 'Qual é a nova pergunta?',
                'answers' => [
                    ['text' => 'Nova Resposta A', 'is_correct' => true, 'action' => 'continue', 'target' => null],
                ]
            ]
        ];

        // 2. Act
        $response = $this->actingAs($user)
            ->put(route('interactions.update', $interaction), $updatedData);

        // 3. Assert
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertDatabaseHas('interactions', [
            'id' => $interaction->id,
            'timestamp' => 20.0,
        ]);
        // Verifica se o JSON na base de dados foi atualizado
        $this->assertEquals('Qual é a nova pergunta?', $interaction->fresh()->data['question']);
    }

    #[Test]
    public function an_authorized_user_can_delete_an_interaction(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);
        $interaction = Interaction::factory()->create(['video_id' => $video->id]);

        $this->app->singleton('current_company', fn() => $company);

        // 2. Act
        $response = $this->actingAs($user)
            ->delete(route('interactions.destroy', $interaction));

        // 3. Assert
        $response->assertRedirect(route('projects.edit', $project));
        $this->assertDatabaseMissing('interactions', ['id' => $interaction->id]);
    }
}
