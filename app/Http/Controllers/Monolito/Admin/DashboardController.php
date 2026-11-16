<?php

namespace App\Http\Controllers\Monolito\Admin;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\BaseController;

class DashboardController  extends BaseController
{
    /**
     * Exibe o dashboard principal da administração.
     */
    public function index(): Response
    {
        // Adiciona os breadcrumbs para a página
        $this->breadcrumbsManager->add('Dashboard', route('admin.dashboard'));

        // Busca as métricas chave
        $totalMembers = User::whereHas('roles', fn($query) => $query->where('name', 'App User'))->count();

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'totalMembers' => $totalMembers,
            ]
        ]);
    }
}
