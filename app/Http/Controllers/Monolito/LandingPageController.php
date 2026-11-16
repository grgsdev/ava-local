<?php

namespace App\Http\Controllers\Monolito;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LandingPageController extends Controller
{
    public function index()
    {
        // No futuro, podemos buscar dados do banco (ex: depoimentos) e passá-los para a página.
        return Inertia::render('Landing', [
            // Adicionamos esta prop para dizer à página qual tema usar
            'layoutTheme' => 'lupa-theme',
        ]);
    }
}
