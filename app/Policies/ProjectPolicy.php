<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'manage projects');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->sameActiveCompany($user, $project)
            && $this->hasPermission($user, 'manage projects');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->allows($user, 'create projects');
    }

    /**
     * Determina se o utilizador pode atualizar o modelo.
     */
    public function update(User $user, Project $project): bool
    {
        return $this->sameActiveCompany($user, $project)
            && $this->hasPermission($user, 'edit projects');
    }

    /**
     * Determina se o utilizador pode excluir o modelo.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->sameActiveCompany($user, $project)
            && $this->hasPermission($user, 'delete projects');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    public function submit(User $user, Project $project): bool
    {
        return $this->sameActiveCompany($user, $project)
            && $this->hasPermission($user, 'edit projects');
    }

    public function approve(User $user, Project $project): bool
    {
        return $this->isMaster($user) || ($this->sameActiveCompany($user, $project)
            && $this->hasPermission($user, 'approve projects'));
    }

    public function reject(User $user, Project $project): bool
    {
        return $this->approve($user, $project);
    }

    public function publish(User $user, Project $project): bool
    {
        return $this->approve($user, $project);
    }

    public function revoke(User $user, Project $project): bool
    {
        return $this->approve($user, $project);
    }

    public function viewPending(User $user): bool
    {
        return $this->isMaster($user) || $this->allows($user, 'approve projects');
    }

    private function isMaster(User $user): bool
    {
        return $user->hasRole('Master');
    }

    private function allows(User $user, string $permission): bool
    {
        return $this->activeCompanyFor($user) !== null
            && $this->hasPermission($user, $permission);
    }

    private function sameActiveCompany(User $user, Project $project): bool
    {
        $company = $this->activeCompanyFor($user);

        return $company !== null && (int) $project->company_id === (int) $company->id;
    }

    private function activeCompanyFor(User $user): ?object
    {
        if (! app()->bound('current_company')) {
            return null;
        }

        $company = app('current_company');

        return $user->companies()
            ->whereKey($company->id)
            ->wherePivot('status', 'active')
            ->exists()
            ? $company
            : null;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        $company = $this->activeCompanyFor($user);

        if (! $company) {
            return false;
        }

        $roleName = $user->companies()
            ->whereKey($company->id)
            ->wherePivot('status', 'active')
            ->first()
            ?->pivot
            ?->role;

        if (! $roleName) {
            return false;
        }

        return Role::where('name', $roleName)->first()?->hasPermissionTo($permission) ?? false;
    }
}
