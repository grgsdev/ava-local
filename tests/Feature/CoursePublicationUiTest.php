<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CoursePublication;
use App\Models\Project;
use App\Models\User;
use App\Services\CoursePublicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoursePublicationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('media');
    }

    #[Test]
    public function the_real_admin_courses_surface_exposes_publication_controls_and_the_stable_public_url_only_to_the_authorized_company_adm(): void
    {
        [$company, $editor, $adm, $course, $project] = $this->project();
        $otherCompany = Company::factory()->create();
        $otherAdm = $this->userInCompany($otherCompany, 'ADM Cliente PJ');

        $this->indexAs($adm, $company)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.state', 'Não publicado')
                ->where('courses.data.0.project.publication.can_publish', true)
                ->where('courses.data.0.project.publication.can_revoke', true)
                ->where('courses.data.0.project.publication.active', false)
                ->missing('courses.data.0.project.publication.public_url'));

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.publish', $project))->assertRedirect();

        $url = route('publications.show', ['token' => $project->fresh()->token]);
        $this->indexAs($adm, $company)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.state', 'Publicado')
                ->where('courses.data.0.project.publication.active', true)
                ->where('courses.data.0.project.publication.public_url', $url));

        $this->actingAs($editor)->withSession(['active_company_id' => $company->id])
            ->get(route('admin.courses.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.can_publish', false)
                ->where('courses.data.0.project.publication.can_revoke', false)
                ->missing('courses.data.0.project.publication.public_url'));

        $this->actingAs($otherAdm)->withSession(['active_company_id' => $otherCompany->id])
            ->get(route('admin.courses.index'))
            ->assertInertia(fn ($page) => $page->component('Admin/Courses/Index')->has('courses.data', 0));

        $this->actingAs($otherAdm)->withSession(['active_company_id' => $otherCompany->id])
            ->post(route('projects.revoke', $project))->assertForbidden();
    }

    #[Test]
    public function revoke_and_republish_update_the_derived_ui_state_without_rotating_the_token(): void
    {
        [$company, , $adm, $course, $project] = $this->project();

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.publish', $project))->assertRedirect();
        $token = $project->fresh()->token;
        $url = route('publications.show', ['token' => $token]);

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.revoke', $project))->assertRedirect();
        $this->indexAs($adm, $company)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.state', 'Publicação revogada')
                ->where('courses.data.0.project.publication.active', false)
                ->missing('courses.data.0.project.publication.public_url'));

        $this->actingAs($adm)->withSession(['active_company_id' => $company->id])
            ->post(route('projects.publish', $project))->assertRedirect();
        $this->assertSame($token, $project->fresh()->token);
        $this->assertSame(2, $project->publications()->count());
        $this->indexAs($adm, $company)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.state', 'Publicado')
                ->where('courses.data.0.project.publication.public_url', $url));

        Course::query()->whereKey($course->id)->update(['updated_at' => now()->addSecond()]);
        $this->indexAs($adm, $company)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Courses/Index')
                ->where('courses.data.0.project.publication.republish_ready', true)
                ->where('courses.data.0.project.publication.state', 'Nova revisão aprovada disponível para republicar'));

        $this->assertSame(1, $project->fresh()->publications()->where('state', CoursePublication::ACTIVE)->count());
        $this->assertStringContainsString('navigator.clipboard.writeText', file_get_contents(resource_path('js/Pages/Admin/Courses/Index.vue')));
    }

    private function indexAs(User $user, Company $company)
    {
        return $this->actingAs($user)
            ->withSession(['active_company_id' => $company->id])
            ->get(route('admin.courses.index'))
            ->assertOk();
    }

    private function project(): array
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $adm = $this->userInCompany($company, 'ADM Cliente PJ');
        $course = Course::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'title' => 'UI source',
            'slug' => 'ui-'.uniqid(),
            'price' => 0,
            'level' => 'beginner',
        ]);
        $project = Project::create([
            'company_id' => $company->id,
            'user_id' => $editor->id,
            'course_id' => $course->id,
            'title' => $course->title,
            'type' => 'course',
            'status' => Project::STATUS_APPROVED,
        ]);

        return [$company, $editor, $adm, $course, $project];
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company, ['role' => $role, 'status' => 'active']);

        return $user;
    }
}
