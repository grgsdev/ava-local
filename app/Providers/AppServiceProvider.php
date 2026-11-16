<?php

namespace App\Providers;

use App\Models\Comment;
use App\Services\BreadcrumbsManager;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role as SpatieRole;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(BreadcrumbsManager::class, function ($app) {
            return new BreadcrumbsManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Comment::observe(\App\Observers\CommentObserver::class);


        // O Gate do Master dá acesso total a tudo
        Gate::before(function (User $user, string $ability) {
            $company = app()->bound('current_company') ? app('current_company') : null;
            if ($company && $user->companies()->where('company_id', $company->id)->first()?->pivot->role === 'Master') {
                return true;
            }
            return null;
        });

        // Este Gate define a lógica para a permissão 'manage team'
        Gate::define('manage team', function (User $user) {
            // 1. Pega a empresa atual
            $company = app('current_company');
            if (!$company) {
                return false;
            }

            // 2. Pega o nome do perfil do utilizador na nossa tabela-pivô
            $roleNameInCompany = $user->companies()->where('company_id', $company->id)->first()?->pivot->role;
            if (!$roleNameInCompany) {
                return false;
            }

            // 3. Encontra o perfil correspondente nas tabelas do Spatie
            $spatieRole = SpatieRole::where('name', $roleNameInCompany)->first();
            if (!$spatieRole) {
                return false;
            }

            // 4. Pergunta ao Spatie se este perfil tem a permissão 'manage team'
            return $spatieRole->hasPermissionTo('manage team');
        });
    }
}
