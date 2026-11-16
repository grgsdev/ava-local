<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProjectFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_interaction_can_be_created_with_a_jump_to_video_action(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);
        // Cria um projeto do tipo 'flow_video'
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id, 'type' => 'flow_video']);
        $videoSource = Video::factory()->create(['project_id' => $project->id]);
        $videoTarget = Video::factory()->create(['project_id' => $project->id]); // O vídeo de destino

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'single_choice',
            'timestamp' => 10,
            'payload' => [
                'question' => 'Para onde vamos agora?',
                'answers' => [
                    // Esta resposta tem a nova ação
                    ['text' => 'Ir para o Vídeo 2', 'is_correct' => true, 'action' => 'jump_to_video', 'target' => $videoTarget->id],
                ]
            ]
        ];

        // 2. Act
        $this->actingAs($user)
            ->post(route('videos.interactions.store', $videoSource), $interactionData);

        // 3. Assert
        $this->assertDatabaseHas('interactions', [
            'video_id' => $videoSource->id,
        ]);

        $interaction = $videoSource->interactions()->first();
        $answer = $interaction->data['answers'][0];

        $this->assertEquals('jump_to_video', $answer['action']);
        $this->assertEquals($videoTarget->id, $answer['target']);
    }
}
