<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Validar o tipo ou assumir 'product' como padrão
        $type = $request->input('type', 'product');

        // Busca a árvore baseada no tipo
        // O scoped do nestedset funciona melhor se filtrarmos antes
        $categories = Category::where('type', $type)
            ->withCount('children') // Para saber se tem filhos na UI
            ->defaultOrder() // Ordenação por _lft
            ->get()
            ->toTree(); // Transforma a lista flat em estrutura hierárquica recursiva

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'type' => $type,
            // Enviamos a lista flat também caso precisemos popular um select de "Pai" no modal de criação
            'flatList' => Category::where('type', $type)
                ->withDepth()
                ->defaultOrder()
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'name' => str_repeat('— ', $c->depth) . $c->name // Visualização hierárquica no select
                ])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:article,product,course',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Verifica duplicidade de slug no mesmo tipo manualmente ou via validação unique composta
        if (Category::where('slug', $validated['slug'])->where('type', $validated['type'])->exists()) {
            return back()->withErrors(['name' => 'Já existe uma categoria com este nome neste grupo.']);
        }

        Category::create($validated);

        return back()->with('success', 'Categoria criada com sucesso.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string'
        ]);

        if ($request->name !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Evitar que uma categoria seja pai dela mesma
        if ($validated['parent_id'] == $category->id) {
            return back()->withErrors(['parent_id' => 'Categoria não pode ser pai dela mesma.']);
        }

        $category->update($validated);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(Category $category)
    {
        // O NestedSet deleta os filhos automaticamente por padrão (Cascade),
        // mas podemos impedir se quisermos segurança.
        $category->delete();

        return back()->with('success', 'Categoria removida.');
    }
}
