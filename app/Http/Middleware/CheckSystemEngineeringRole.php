<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemEngineeringRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verifica se o usuário está autenticado
        // 2. Verifica se o usuário autenticado tem o papel 'System Engineering'
        if (!auth()->check() || !auth()->user()->hasAnyRole(['System Engineering', 'System Developer'])) {
            // Se não tiver, aborta a requisição com um erro 403 (Acesso Proibido)
            abort(403, 'Acesso não autorizado.');
        }

        // Se tiver, permite que a requisição continue
        return $next($request);
    }
}
