<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionDragAndDropTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_create_a_drag_and_drop_interaction(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'drag_and_drop',
            'timestamp' => 25,
            'payload' => [
                'question' => 'Associe o animal ao seu habitat:',
                'draggables' => [
                    ['id' => 'drag_1', 'text' => 'Leão'],
                    ['id' => 'drag_2', 'text' => 'Tubarão'],
                ],
                'dropzones' => [
                    ['id' => 'zone_A', 'label' => 'Savana', 'correct_item_id' => 'drag_1'],
                    ['id' => 'zone_B', 'label' => 'Oceano', 'correct_item_id' => 'drag_2'],
                ]
            ]
        ];

        // 2. Act
        $response = $this->actingAs($user)
            ->post(route('videos.interactions.store', $video), $interactionData);

        // 3. Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('interactions', [
            'video_id' => $video->id,
            'type' => 'drag_and_drop',
        ]);

        // Verifica se a estrutura JSON foi guardada corretamente
        $interaction = $video->interactions()->first();
        $this->assertEquals('Associe o animal ao seu habitat:', $interaction->data['question']);
        $this->assertEquals('Leão', $interaction->data['draggables'][0]['text']);
        $this->assertEquals('drag_1', $interaction->data['dropzones'][0]['correct_item_id']);
    }
}
