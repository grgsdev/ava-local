<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Se o usuário não tiver a permissão 'access panel', desloga-o e redireciona.
        if (! $request->user()->can('access panel')) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Você não tem permissão para aceder a esta área.',
            ]);
        }

        return $next($request);
    }
}
