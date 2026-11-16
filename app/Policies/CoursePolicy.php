<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Course;
use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Role;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isMaster($user) || $this->hasCompanyPermission($user, 'manage projects');
    }

    public function view(User $user, Course $course): bool
    {
        if ($course->is_publication_snapshot) {
            return false;
        }

        return $this->isMaster($user) || ($this->belongsToActiveCompany($user, $course)
            && $this->hasCompanyPermission($user, 'manage projects'));
    }

    public function create(User $user): bool
    {
        return ! $this->isMaster($user) && $this->hasCompanyPermission($user, 'create projects');
    }

    public function update(User $user, Course $course): bool
    {
        if ($course->is_publication_snapshot) {
            return false;
        }

        if ($this->isMaster($user)) {
            return true;
        }

        if (! $this->belongsToActiveCompany($user, $course)
            || ! $this->hasCompanyPermission($user, 'edit projects')) {
            return false;
        }

        return $course->project?->status !== Project::STATUS_PENDING_APPROVAL
            || $this->hasCompanyPermission($user, 'approve projects');
    }

    public function delete(User $user, Course $course): bool
    {
        if ($course->is_publication_snapshot) {
            return false;
        }

        return $this->isMaster($user) || ($this->belongsToActiveCompany($user, $course)
            && $this->hasCompanyPermission($user, 'delete projects'));
    }

    public function submit(User $user, Course $course): bool
    {
        if ($course->is_publication_snapshot) {
            return false;
        }

        return ! $this->isMaster($user)
            && $this->belongsToActiveCompany($user, $course)
            && $this->hasCompanyPermission($user, 'edit projects');
    }

    private function isMaster(User $user): bool
    {
        return $user->hasRole('Master');
    }

    private function belongsToActiveCompany(User $user, Course $course): bool
    {
        $company = $this->activeCompany($user);

        return $company !== null && (int) $course->company_id === (int) $company->id;
    }

    private function hasCompanyPermission(User $user, string $permission): bool
    {
        $company = $this->activeCompany($user);

        if (! $company) {
            return false;
        }

        $roleName = $user->companies()
            ->whereKey($company->id)
            ->wherePivot('status', 'active')
            ->first()
            ?->pivot
            ?->role;

        return $roleName !== null
            && Role::where('name', $roleName)->first()?->hasPermissionTo($permission) === true;
    }

    private function activeCompany(User $user): ?Company
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
}
