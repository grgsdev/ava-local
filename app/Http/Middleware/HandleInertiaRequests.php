<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Http\Request;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use App\Services\BreadcrumbsManager;
use App\Http\Resources\Api\V1\PlatformSettingResource;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = app()->getLocale();
        if ($request->session()->has('locale')) {
            App::setLocale($request->session()->get('locale'));
            $locale = app()->getLocale();
        }

        if ($request->hasHeader('X-Locale')) {
            $locale = $request->header('X-Locale');
            if ($locale) {
                app()->setLocale($locale);
            }
        }
        return [
            ...parent::share($request),
            'auth' => fn() => [
                // Verificamos se o usuário está logado
                'user' => $request->user() ? [
                    // Enviamos apenas os dados necessários para o frontend
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'avatar_url' => $request->user()->getFirstMediaUrl('avatar'),
                    'is_master' => $request->user()->hasRole('Master'),
                    'notifications' => $request->user()->unreadNotifications,
                    'notification_count' => $request->user()->unreadNotifications->count(),

                    // A MÁGICA ACONTECE AQUI: Buscamos todas as permissões do usuário
                    // e extraímos apenas os nomes para um array.
                    'can' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null, // Se não estiver logado, o usuário é nulo.
                'company_can' => $this->companyPermissions($user),
                'unread_notifications_count' => $user ? $user->unreadNotifications()->count() : 0,
                // Opcional: Carregar as 5 últimas notificações para mostrar no dropdown imediatamente
                'notifications' => $user ? $user->notifications()->latest()->take(5)->get()->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'data' => $n->data,
                        'created_at_human' => $n->created_at->diffForHumans(),
                        'read_at' => $n->read_at,
                    ];
                }) : [],
            ],
            'active_company' => fn() => $this->activeCompany(),
            'flash' => fn() => $request->session()->get('flash'),
            // 'flash' => [
            //     'message' => fn() => $request->session()->get('message'),
            // ],
            'platformSettings' => function () {
                // 3. Busca as configurações (usando o cache para performance)
                // e formata-as com o nosso Resource.
                $settings = PlatformSetting::first();
                return $settings ? new PlatformSettingResource($settings) : null;
            },
            'locale' =>  $locale,
            'theme' => $request->cookie('theme', 'light'),
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'breadcrumbs' => fn() => app(BreadcrumbsManager::class)->get(), // 2. Pega as migalhas do serviço

            'appName' => config('app.name'),
        ];
    }

    private function companyPermissions($user): array
    {
        if (! $user || ! app()->bound('current_company')) {
            return [];
        }

        $company = app('current_company');
        $membership = $user->companies()
            ->whereKey($company->id)
            ->wherePivot('status', 'active')
            ->first();

        if (! $membership?->pivot?->role) {
            return [];
        }

        return Role::where('name', $membership->pivot->role)
            ->first()
            ?->getAllPermissions()
            ->pluck('name')
            ->values()
            ->all() ?? [];
    }

    private function activeCompany(): ?array
    {
        if (! app()->bound('current_company')) {
            return null;
        }

        $company = app('current_company');

        return [
            'id' => $company->id,
            'name' => $company->name,
        ];
    }
}
