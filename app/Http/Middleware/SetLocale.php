<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['pt_BR', 'en', 'fr', 'it', 'de', 'es', 'de_CH'];
        $locale = null;

        // 1. Prioridade máxima: Parâmetro na query string
        if ($request->has('locale') && in_array($request->input('locale'), $supportedLocales)) {
            $locale = $request->input('locale');
        }
        // 2. Prioridade para APIs: Cabeçalho customizado
        elseif ($request->hasHeader('X-Locale') && in_array($request->header('X-Locale'), $supportedLocales)) {
            $locale = $request->header('X-Locale');
        }
        // 3. Prioridade para Web: Sessão
        elseif ($request->session()->has('locale') && in_array($request->session()->get('locale'), $supportedLocales)) {
            $locale = $request->session()->get('locale');
        }
        // 4. Fallback para Web: Cookie
        elseif ($request->hasCookie('preferred_language') && in_array($request->cookie('preferred_language'), $supportedLocales)) {
            $locale = $request->cookie('preferred_language');
        }

        // Define o locale se um foi encontrado, senão usa o padrão
        app()->setLocale($locale ?? config('app.locale'));

        // Executa o resto da aplicação
        $response = $next($request);

        // Para requisições WEB, anexa o cookie à resposta para persistir a escolha
        // Não fazemos isso para a API para mantê-la stateless.
        if (!$request->is('api/*') && $locale) {
            return $response->cookie('preferred_language', $locale, 60 * 24 * 365); // 1 ano
        }

        return $response;
    }
}
