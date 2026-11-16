<?php

namespace App\Http\Controllers;

use App\Services\BreadcrumbsManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * A instância do gestor de breadcrumbs.
     *
     * @var \App\Services\BreadcrumbsManager
     */
    protected $breadcrumbsManager;

    /**
     * O construtor do BaseController.
     * Injeta automaticamente o BreadcrumbsManager para que todos os
     * controllers que herdem desta classe já o tenham disponível.
     *
     * @param \App\Services\BreadcrumbsManager $breadcrumbsManager
     */
    public function __construct(BreadcrumbsManager $breadcrumbsManager)
    {
        $this->breadcrumbsManager = $breadcrumbsManager;
    }
}
