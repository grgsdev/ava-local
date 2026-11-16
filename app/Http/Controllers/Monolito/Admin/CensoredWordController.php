<?php

namespace App\Http\Controllers\Monolito\Admin;

use App\Http\Controllers\BaseController;
use Inertia\Inertia;
use App\Models\CensoredWord;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CensoredWordController extends BaseController
{
    public function index()
    {
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Moderação', '') // Link nulo para item não clicável
            ->add(__('Palavras Censuradas'), '');

        $perPage = request()->input('per_page', 15);

        $censoredWords = QueryBuilder::for(CensoredWord::class)
            ->allowedFilters(['word'])
            ->defaultSort('-created_at')
            ->allowedSorts(['word', 'created_at'])
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/CensoredWords/Index', [
            'censoredWords' => $censoredWords,
            'queryParams' => request()->query() ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => 'required|string|max:255|unique:censored_words,word',
        ]);

        CensoredWord::create($validated);

        return back()->with('success', 'Palavra adicionada com sucesso.');
    }

    public function destroy(CensoredWord $censoredWord)
    {
        $censoredWord->delete();

        return back()->with('success', 'Palavra removida com sucesso.');
    }
}
