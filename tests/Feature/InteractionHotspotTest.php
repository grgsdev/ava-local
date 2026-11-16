<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionHotspotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_create_a_hotspot_interaction(): void
    {
        // 1. Arrange
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);
        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'hotspot',
            'timestamp' => 10, // Momento em que o hotspot aparece
            'payload' => [
                'duration' => 5, // Duração que o hotspot fica na tela
                'position' => ['x' => 20, 'y' => 30], // Posição em %
                'size' => ['width' => 10, 'height' => 15], // Tamanho em %
                'action' => 'jump_to_time',
                'target' => 45,
            ]
        ];

        // 2. Act
        $response = $this->actingAs($user)
            ->post(route('videos.interactions.store', $video), $interactionData);

        // 3. Assert
        $response->assertRedirect();
        $this->assertDatabaseHas('interactions', [
            'video_id' => $video->id,
            'type' => 'hotspot',
        ]);

        $interaction = $video->interactions()->first();
        $this->assertEquals(20, $interaction->data['position']['x']);
        $this->assertEquals(5, $interaction->data['duration']);
        $this->assertEquals('jump_to_time', $interaction->data['action']);
    }
}
