<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\System\StoreModelRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Requests\System\UpdateModelRequest;

class GeneratorController extends Controller
{
    private function getModelDirectory(): string
    {
        // Garante que o diretório exista
        $directory = base_path("documentation/models/json");
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        return $directory;
    }
    private function getModelPath(string $model): string
    {
        $directory = $this->getModelDirectory();

        // Verifica e cria o diretório se não existir
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        return $directory . '/' . $model;
    }

    public function index(Request $request)
    {
        $queryParams = $request->all(['sort', 'filter', 'per_page']);
        $search = $request->input('filter.search');
        $sortField = $request->input('sort', '-updated_at'); // Padrão
        $perPage = $request->input('per_page', 15);

        $allModels = Cache::rememberForever('generator_models_list', function () {
            $files = File::glob($this->getModelDirectory() . '/*.json');
            $models = [];
            foreach ($files as $file) {
                $content = json_decode(File::get($file), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $models[] = [
                        'name' => File::name($file),
                        'version' => $content['version'] ?? 'N/A',
                        'attributes_count' => count($content['attributes'] ?? []),
                        'relations_count' => count($content['relations'] ?? []),
                        'updated_at' => $content['updated_at'] ?? date('Y-m-d H:i:s', File::lastModified($file)),
                    ];
                }
            }
            return $models;
        });

        $modelsCollection = collect($allModels)
            ->when($search, function ($collection) use ($search) {
                return $collection->filter(fn($model) => stripos($model['name'], $search) !== false);
            })
            ->sortBy(ltrim($sortField, '-'), SORT_REGULAR, str_starts_with($sortField, '-'));

        $paginatedModels = new LengthAwarePaginator(
            $modelsCollection->forPage(Paginator::resolveCurrentPage('page'), $perPage)->values(),
            $modelsCollection->count(),
            $perPage,
            Paginator::resolveCurrentPage('page'),
            ['path' => $request->url()]
        );

        return Inertia::render('System/Generator/Index', [
            'models' => $paginatedModels->withQueryString(),
            'queryParams' => $queryParams,
        ]);
    }

    public function create()
    {
        return Inertia::render('System/Generator/Create');
    }

    public function store(StoreModelRequest $request)
    {
        $validated = $request->validated();
        $filename = $validated['name'] . '.json';
        $path = $this->getModelPath($filename);

        try {
            File::put(
                $path,
                json_encode(array_merge($validated, [
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            Cache::forget('generator_models_list');

            return redirect()->route('system.generator.index')
                ->with(
                    'flash',
                    [
                        'type' => 'success', // ou 'error'
                        'message' => 'Ação executada com sucesso.',
                    ]
                );
        } catch (\Exception $e) {
            return back()->withErrors(['geral' => 'Falha ao criar o arquivo: ' . $e->getMessage()]);
        }
    }
    public function show(string $model)
    {
        $path = $this->getModelPath($model . '.json');

        if (!File::exists($path)) {
            abort(404, 'Arquivo de modelo não encontrado.');
        }

        $content = json_decode(File::get($path), true);

        return Inertia::render('System/Generator/Show', [
            'model' => $content,
        ]);
    }
    public function edit(string $model)
    {
        $path = $this->getModelPath($model . '.json');

        if (!File::exists($path)) {
            abort(404, 'Arquivo de modelo não encontrado.');
        }

        $content = json_decode(File::get($path), true);

        return Inertia::render('System/Generator/Edit', [
            'model' => $content,
        ]);
    }

    public function update(UpdateModelRequest $request, string $model)
    {
        $path = $this->getModelPath($model . '.json');
        if (!File::exists($path)) {
            abort(404);
        }

        $validated = $request->validated();

        try {
            $currentContent = json_decode(File::get($path), true);
            $createdAt = $currentContent['created_at'] ?? now()->toDateTimeString();

            File::put(
                $path,
                json_encode(array_merge($validated, [
                    'created_at' => $createdAt,
                    'updated_at' => now()->toDateTimeString(),
                ]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            Cache::forget('generator_models_list');

            return redirect()->route('system.generator.index')
                ->with(
                    'flash',
                    [
                        'type' => 'success', // ou 'error'
                        'message' => 'Ação executada com sucesso.',
                    ]
                );
        } catch (\Exception $e) {
            return back()->withErrors(['geral' => 'Falha ao atualizar o arquivo: ' . $e->getMessage()]);
        }
    }

    public function destroy(string $model)
    {
        $path = $this->getModelPath($model . '.json');
        if (!File::exists($path)) {
            abort(404);
        }

        try {
            File::delete($path);
            Cache::forget('generator_models_list');
            return redirect()->route('system.generator.index')
                ->with(
                    'flash',
                    [
                        'type' => 'success', // ou 'error'
                        'message' => 'Ação executada com sucesso.',
                    ]
                );
        } catch (\Exception $e) {
            return back()->withErrors(['geral' => 'Falha ao apagar o arquivo: ' . $e->getMessage()]);
        }
    }

    public function exportPdf(string $model)
    {
        $path = $this->getModelPath($model . '.json');
        if (!File::exists($path)) {
            abort(404);
        }

        $content = json_decode(File::get($path), true);

        // Carrega a view Blade com os dados e gera o PDF
        $pdf = Pdf::loadView('pdf.model-definition', ['model' => $content]);

        // Retorna o PDF para download
        return $pdf->download("modelo-{$model}.pdf");
    }

    public function runGenerator(Request $request, string $model)
    {
        $path = $this->getModelPath($model . '.json');
        if (!File::exists($path)) {
            return back()->withErrors(['geral' => 'Arquivo de modelo não encontrado.']);
        }

        $validated = $request->validate([
            'commands' => ['required', 'array'],
            'commands.*' => ['string', 'in:grgsdev:migration,grgsdev:model,grgsdev:formrequest,grgsdev:resource,grgsdev:controller'],
        ]);

        try {
            foreach ($validated['commands'] as $command) {
                Artisan::call($command, ['model' => $model]);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['geral' => 'Falha ao executar os comandos: ' . $e->getMessage()]);
        }

        return redirect()->route('system.generator.index')
            ->with(
                'flash',
                [
                    'type' => 'success', // ou 'error'
                    'message' => 'Ação executada com sucesso.',
                ]
            );
    }
}
