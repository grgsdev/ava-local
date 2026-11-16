<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreMasterCompanyRequest;
use App\Http\Requests\StoreMasterCompanyAdmRequest;
use App\Models\Company;
use App\Models\User;
use App\Notifications\InviteUserToCompleteRegistration;
use App\Services\PendingUserInvitationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterCompanyController extends BaseController
{
    public function index(Request $request)
    {
        $this->ensureMaster($request);

        return Inertia::render('Admin/Companies/Index', [
            'companies' => Company::query()->withCount('users')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureMaster($request);

        return Inertia::render('Admin/Companies/Form');
    }

    public function store(StoreMasterCompanyRequest $request)
    {
        $this->ensureMaster($request);
        $company = Company::create($request->validated());

        return redirect()->route('admin.companies.show', $company);
    }

    public function show(Request $request, Company $company)
    {
        $this->ensureMaster($request);

        return Inertia::render('Admin/Companies/Show', [
            'company' => $company->only(['id', 'name']),
            'adms' => $company->users()
                ->wherePivot('role', 'ADM Cliente PJ')
                ->get(['users.id', 'users.name', 'users.email'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]),
        ]);
    }

    public function edit(Request $request, Company $company)
    {
        $this->ensureMaster($request);

        return Inertia::render('Admin/Companies/Form', ['company' => $company->only(['id', 'name'])]);
    }

    public function update(StoreMasterCompanyRequest $request, Company $company)
    {
        $this->ensureMaster($request);
        $company->update($request->validated());

        return redirect()->route('admin.companies.show', $company);
    }

    public function createAdm(Request $request, Company $company)
    {
        $this->ensureMaster($request);

        return Inertia::render('Admin/Companies/AdmForm', ['company' => $company->only(['id', 'name'])]);
    }

    public function storeAdm(StoreMasterCompanyAdmRequest $request, Company $company)
    {
        $this->ensureMaster($request);
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            $user = app(PendingUserInvitationService::class)->create(
                $data['name'],
                $data['email'],
            );
        } elseif ($user->email_verified_at === null && ! $user->is_active) {
            $user->notify(new InviteUserToCompleteRegistration());
        }

        $company->users()->syncWithoutDetaching([
            $user->id => ['role' => 'ADM Cliente PJ', 'status' => 'active'],
        ]);

        return redirect()->route('admin.companies.show', $company);
    }

    private function ensureMaster(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Master'), 403);
    }
}
