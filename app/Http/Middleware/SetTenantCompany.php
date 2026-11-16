<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->hasRole('Master')) {
            return $next($request);
        }

        $memberships = $user->companies()
            ->wherePivot('status', 'active')
            ->get();

        if ($memberships->isEmpty()) {
            $request->session()->forget('active_company_id');

            abort(403, 'No active company membership.');
        }

        $selectedId = $request->session()->get('active_company_id');
        $hadSelection = $selectedId !== null;
        $company = $selectedId === null
            ? null
            : $memberships->firstWhere('id', (int) $selectedId);

        if (! $company && ! $hadSelection && $memberships->count() === 1) {
            $company = $memberships->first();
            $request->session()->put('active_company_id', $company->id);
        }

        if (! $company) {
            $request->session()->forget('active_company_id');
            $request->session()->put('company_context.intended_url', $request->fullUrl());

            return redirect()->route('companies.select');
        }

        app()->instance('current_company', $company);

        return $next($request);
    }
}
