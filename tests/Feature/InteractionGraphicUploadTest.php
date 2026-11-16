<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Interaction;
use App\Models\Project;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InteractionGraphicUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_authorized_user_can_upload_a_graphic_when_creating_an_interaction(): void
    {
        // 1. Arrange
        Storage::fake('media'); // Cria um disco de armazenamento falso para os testes

        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => 'Editor']);

        $project = Project::factory()->create(['company_id' => $company->id, 'user_id' => $user->id]);
        $video = Video::factory()->create(['project_id' => $project->id]);

        $this->app->singleton('current_company', fn() => $company);

        $interactionData = [
            'type' => 'single_choice',
            'timestamp' => 10,
            'payload' => [
                'question' => 'Qual animal está na imagem?',
                'answers' => [['text' => 'Gato', 'is_correct' => true]],
            ],
            'graphic' => UploadedFile::fake()->image('gato.jpg'), // Cria um ficheiro de imagem falso
        ];

        // 2. Act
        $this->actingAs($user)
            ->post(route('videos.interactions.store', $video), $interactionData);

        // 3. Assert
        $this->assertDatabaseHas('interactions', ['video_id' => $video->id]);

        $interaction = Interaction::first();

        // Verifica se a media foi associada à interação
        $this->assertTrue($interaction->hasMedia('interaction_graphics'));

        // Verifica se o ficheiro existe no disco falso
        $interaction->getMedia('interaction_graphics')->each(function ($media) {
            Storage::disk('media')->assertExists($media->id . '/' . $media->file_name);
        });
    }
}
