<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectCompanyRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CompanyContextController extends BaseController
{
    public function create(Request $request)
    {
        $companies = $request->user()
            ->companies()
            ->wherePivot('status', 'active')
            ->get(['companies.id', 'companies.name']);

        abort_if($companies->isEmpty(), 403, 'No active company membership.');

        return Inertia::render('Companies/Select', [
            'companies' => $companies,
        ]);
    }

    public function store(SelectCompanyRequest $request)
    {
        $company = $request->user()
            ->companies()
            ->whereKey($request->integer('company_id'))
            ->wherePivot('status', 'active')
            ->first();

        abort_unless($company, 403, 'Invalid company membership.');

        $request->session()->put('active_company_id', $company->id);
        $intended = $request->session()->pull('company_context.intended_url');

        return redirect()->to($intended ?: route('projects.index'));
    }
}
