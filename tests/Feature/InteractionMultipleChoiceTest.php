<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionMultipleChoiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_create_a_multiple_choice_interaction(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'multiple_choice',
            'timestamp' => 30,
            'payload' => [
                'question' => 'Quais destes são mamíferos?',
                'answers' => [
                    ['text' => 'Baleia', 'is_correct' => true],
                    ['text' => 'Tubarão', 'is_correct' => false],
                    ['text' => 'Morcego', 'is_correct' => true],
                    ['text' => 'Pinguim', 'is_correct' => false],
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
            'type' => 'multiple_choice',
        ]);

        $interaction = $video->interactions()->first();
        $answers = $interaction->data['answers'];
        $this->assertTrue($answers[0]['is_correct']); // Baleia é correta
        $this->assertFalse($answers[1]['is_correct']); // Tubarão é incorreta
        $this->assertTrue($answers[2]['is_correct']); // Morcego é correta
    }
}
