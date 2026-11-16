<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Notifications\InviteUserToCompleteRegistration;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MasterCompanyAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function master(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Master');

        return $user;
    }

    #[Test] public function master_lists_creates_and_edits_companies(): void
    {
        $master = $this->master();
        $company = Company::factory()->create(['name' => 'Original']);

        $this->actingAs($master)->get(route('admin.companies.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.is_master', true));
        $this->post(route('admin.companies.store'), ['name' => 'Nova'])->assertRedirect();

        $created = Company::where('name', 'Nova')->firstOrFail();
        $this->get(route('admin.companies.show', $created))->assertOk();

        $this->put(route('admin.companies.update', $created), ['name' => 'Atualizada'])->assertRedirect();

        $this->assertDatabaseHas('companies', ['id' => $created->id, 'name' => 'Atualizada']);
    }

    #[Test] public function non_master_cannot_access_global_company_administration(): void
    {
        $company = Company::factory()->create();
        $nonMaster = User::factory()->create();

        $this->actingAs($nonMaster)->get(route('admin.companies.index'))->assertForbidden();
        $this->actingAs($nonMaster)->post(route('admin.companies.store'), ['name' => 'Blocked'])->assertForbidden();
        $this->actingAs($nonMaster)->get(route('admin.companies.show', $company))->assertForbidden();
        $this->actingAs($nonMaster)->post(route('admin.companies.adms.store', $company), [
            'name' => 'ADM',
            'email' => 'adm@example.test',
        ])->assertForbidden();
    }

    #[Test] public function master_creates_a_pending_adm_membership_with_the_existing_onboarding(): void
    {
        Notification::fake();
        $master = $this->master();
        $company = Company::factory()->create();

        $this->actingAs($master)->post(route('admin.companies.adms.store', $company), [
            'name' => 'ADM',
            'email' => 'adm@example.test',
        ])->assertRedirect();

        $adm = User::where('email', 'adm@example.test')->firstOrFail();

        $this->assertFalse($adm->hasRole('Master'));
        $this->assertFalse($adm->is_active);
        $this->assertNull($adm->email_verified_at);
        $this->assertNotNull($adm->verification_sent_at);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $adm->id,
            'role' => 'ADM Cliente PJ',
            'status' => 'active',
        ]);
        Notification::assertSentTo($adm, InviteUserToCompleteRegistration::class);

        $this->post(route('admin.companies.adms.store', $company), [
            'name' => 'Ignored',
            'email' => 'adm@example.test',
        ])->assertRedirect();

        $this->assertSame(1, $company->users()->whereKey($adm->id)->count());
        $this->assertSame(1, User::where('email', 'adm@example.test')->count());
    }

    #[Test] public function a_new_adm_completes_the_existing_invitation_and_enters_its_only_company_context(): void
    {
        Notification::fake();
        $master = $this->master();
        $company = Company::factory()->create();

        $this->actingAs($master)->post(route('admin.companies.adms.store', $company), [
            'name' => 'ADM',
            'email' => 'adm@example.test',
        ])->assertRedirect();

        $adm = User::where('email', 'adm@example.test')->firstOrFail();
        $url = URL::temporarySignedRoute('registration.complete', now()->addHours(48), ['user' => $adm->id]);

        $this->post($url, [
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_number' => '11999999999',
            'birth_date' => '1990-01-01',
        ])->assertRedirect(route('dashboard'));

        $adm->refresh();
        $this->assertAuthenticatedAs($adm);
        $this->assertTrue($adm->is_active);
        $this->assertNotNull($adm->email_verified_at);
        $this->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('active_company_id', $company->id);
        $this->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.user.is_master', false));
        $this->get(route('admin.companies.index'))->assertForbidden();
    }

    #[Test] public function master_reuses_existing_user_without_removing_other_company_memberships(): void
    {
        $master = $this->master();
        $targetCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.test']);

        $otherCompany->users()->attach($existingUser, ['role' => 'Editor', 'status' => 'active']);

        $this->actingAs($master)->post(route('admin.companies.adms.store', $targetCompany), [
            'name' => 'Existing user',
            'email' => 'existing@example.test',
        ])->assertRedirect();

        $this->assertSame(1, User::where('email', 'existing@example.test')->count());
        $this->assertDatabaseHas('company_user', [
            'company_id' => $targetCompany->id,
            'user_id' => $existingUser->id,
            'role' => 'ADM Cliente PJ',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $otherCompany->id,
            'user_id' => $existingUser->id,
            'role' => 'Editor',
            'status' => 'active',
        ]);
    }

    #[Test] public function master_company_and_adm_requests_reject_invalid_input(): void
    {
        $master = $this->master();
        $company = Company::factory()->create();

        $this->actingAs($master)->post(route('admin.companies.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->post(route('admin.companies.adms.store', $company), [
            'name' => '',
            'email' => 'invalid-email',
        ])->assertSessionHasErrors(['name', 'email']);
    }
}
