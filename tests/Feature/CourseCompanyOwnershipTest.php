<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseModule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CourseCompanyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function the_course_company_column_is_nullable_and_enforces_a_restrictive_foreign_key(): void
    {
        $this->assertTrue(Schema::hasColumn('courses', 'company_id'));

        $author = User::factory()->create();
        $legacyCourse = $this->course(null, $author);
        $this->assertNull($legacyCourse->company_id);

        $this->expectException(QueryException::class);
        Course::create([
            'company_id' => 999999,
            'user_id' => $author->id,
            'title' => 'Invalid company course',
            'slug' => 'invalid-company-course',
            'price' => 0,
            'level' => 'beginner',
        ]);
    }

    #[Test]
    public function a_referenced_company_cannot_be_deleted_while_its_course_exists(): void
    {
        $company = Company::factory()->create();
        $this->course($company, User::factory()->create());

        $this->expectException(QueryException::class);
        $company->delete();
    }

    #[Test]
    public function new_adm_and_editor_courses_use_the_active_company_and_ignore_company_input(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        foreach (['ADM Cliente PJ', 'Editor'] as $role) {
            $user = $this->userInCompany($companyA, $role);
            $title = 'Course '.$role;

            $this->actingAs($user)
                ->withSession(['active_company_id' => $companyA->id])
                ->post(route('admin.courses.store'), [
                    'title' => $title,
                    'price' => 0,
                    'level' => 'beginner',
                    'company_id' => $companyB->id,
                ])
                ->assertRedirect();

            $course = Course::query()->where('title', $title)->firstOrFail();
            $this->assertSame($companyA->id, $course->company_id);
            $this->assertSame($user->id, $course->user_id);
        }
    }

    #[Test]
    public function adm_and_editor_only_list_and_access_courses_from_their_active_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $author = User::factory()->create();
        $courseA = $this->course($companyA, $author);
        $courseB = $this->course($companyB, $author);
        $legacyCourse = $this->course(null, $author);

        foreach (['ADM Cliente PJ', 'Editor'] as $role) {
            $user = $this->userInCompany($companyA, $role);

            $this->actingAs($user)
                ->withSession(['active_company_id' => $companyA->id])
                ->get(route('admin.courses.index'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Courses/Index')
                    ->has('courses.data', 1)
                    ->where('courses.data.0.id', $courseA->id));

            $this->get(route('admin.courses.edit', $courseB))->assertForbidden();
            $this->put(route('admin.courses.update', $courseB), [
                'title' => 'Attempted cross-company update',
                'price' => 0,
                'level' => 'beginner',
            ])->assertForbidden();
            $this->get(route('admin.courses.builder.index', $courseB))->assertForbidden();
            $this->get(route('admin.courses.edit', $legacyCourse))->assertForbidden();
        }

        $adm = $this->userInCompany($companyA, 'ADM Cliente PJ');
        $this->actingAs($adm)
            ->withSession(['active_company_id' => $companyA->id])
            ->delete(route('admin.courses.destroy', $courseB))
            ->assertForbidden();
    }

    #[Test]
    public function builder_subroutes_cannot_mix_a_course_with_another_courses_module(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $user = $this->userInCompany($companyA, 'Editor');
        $courseA = $this->course($companyA, $user);
        $courseB = $this->course($companyB, User::factory()->create());
        $moduleB = CourseModule::create([
            'course_id' => $courseB->id,
            'title' => 'Other company module',
            'order_column' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['active_company_id' => $companyA->id])
            ->post(route('admin.courses.builder.lessons.store', [$courseA, $moduleB]), [
                'title' => 'Attempted cross-company lesson',
                'video_url' => 'https://example.test/video',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function builder_data_endpoint_rejects_a_course_from_another_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editor = $this->userInCompany($companyA, 'Editor');
        $courseB = $this->course($companyB, User::factory()->create());
        $moduleB = CourseModule::create([
            'course_id' => $courseB->id,
            'title' => 'Other company module',
            'order_column' => 1,
        ]);
        $lessonB = CourseLesson::create([
            'course_module_id' => $moduleB->id,
            'title' => 'Other company lesson',
            'slug' => 'other-company-lesson',
            'video_provider' => 'external',
            'video_url' => 'https://example.test/video',
            'order_column' => 1,
        ]);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $companyA->id])
            ->get(route('courses.builder.lessons.data', [$courseB, $lessonB]))
            ->assertForbidden();
    }

    #[Test]
    public function an_editor_cannot_delete_a_course_from_its_own_company_but_keeps_update_and_builder_access(): void
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $course = $this->course($company, $editor);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $company->id])
            ->put(route('admin.courses.update', $course), [
                'title' => 'Editor updated course',
                'price' => 0,
                'level' => 'beginner',
            ])
            ->assertRedirect(route('admin.courses.index'));

        $this->get(route('admin.courses.builder.index', $course))->assertOk();
        $this->delete(route('admin.courses.destroy', $course))->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'deleted_at' => null]);
    }

    #[Test]
    public function adm_and_master_keep_their_existing_course_delete_behavior(): void
    {
        $company = Company::factory()->create();
        $author = User::factory()->create();
        $adm = $this->userInCompany($company, 'ADM Cliente PJ');
        $admCourse = $this->course($company, $author);

        $this->actingAs($adm)
            ->withSession(['active_company_id' => $company->id])
            ->delete(route('admin.courses.destroy', $admCourse))
            ->assertRedirect();
        $this->assertSoftDeleted('courses', ['id' => $admCourse->id]);

        $master = User::factory()->create();
        $master->assignRole('Master');
        $masterCourse = $this->course($company, $author);

        $this->actingAs($master)
            ->delete(route('admin.courses.destroy', $masterCourse))
            ->assertRedirect();
        $this->assertSoftDeleted('courses', ['id' => $masterCourse->id]);
    }

    #[Test]
    public function an_authorized_editor_keeps_the_mature_builder_module_and_lesson_flow(): void
    {
        $company = Company::factory()->create();
        $editor = $this->userInCompany($company, 'Editor');
        $course = $this->course($company, $editor);

        $this->actingAs($editor)
            ->withSession(['active_company_id' => $company->id])
            ->post(route('admin.courses.builder.modules.store', $course), [
                'title' => 'Authorized module',
            ])
            ->assertRedirect();

        $module = CourseModule::query()
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->post(route('admin.courses.builder.lessons.store', [$course, $module]), [
            'title' => 'Authorized lesson',
            'video_url' => 'https://example.test/video',
        ])->assertRedirect();

        $this->assertDatabaseHas('course_modules', [
            'id' => $module->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('course_lessons', [
            'course_module_id' => $module->id,
            'title' => 'Authorized lesson',
        ]);
    }

    #[Test]
    public function master_retains_global_read_access_to_company_and_legacy_courses_without_membership(): void
    {
        $master = User::factory()->create();
        $master->assignRole('Master');
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $author = User::factory()->create();
        $courseA = $this->course($companyA, $author);
        $courseB = $this->course($companyB, $author);
        $legacyCourse = $this->course(null, $author);

        $this->actingAs($master)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('courses.data', 3));

        $this->get(route('admin.courses.edit', $courseA))->assertOk();
        $this->get(route('admin.courses.edit', $courseB))->assertOk();
        $this->get(route('admin.courses.edit', $legacyCourse))->assertOk();
        $this->get(route('admin.courses.create'))->assertForbidden();
    }

    private function userInCompany(Company $company, string $role): User
    {
        $user = User::factory()->create();
        $user->companies()->attach($company->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function course(?Company $company, User $author): Course
    {
        static $sequence = 1;
        $suffix = $sequence++;

        return Course::create([
            'company_id' => $company?->id,
            'user_id' => $author->id,
            'title' => 'Synthetic Course '.$suffix,
            'slug' => 'synthetic-course-'.$suffix,
            'price' => 0,
            'level' => 'beginner',
        ]);
    }
}
