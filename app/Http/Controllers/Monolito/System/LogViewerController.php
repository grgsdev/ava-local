<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate; // Para autorização

class LogViewerController extends BaseController
{
    /**
     * Resume a contagem de cada nível de log em um arquivo.
     * @param string $content
     * @return array
     */
    private function summarizeLogLevels(string $content): array
    {
        // Regex para encontrar apenas os níveis de log de forma eficiente
        preg_match_all('/\] \w+\.(\w+):/m', $content, $matches);
        $summary = [];
        if (isset($matches[1])) {
            $levels = array_map('strtolower', $matches[1]);
            $summary = array_count_values($levels);
            ksort($summary); // Ordena os níveis alfabeticamente (alert, critical, error, etc.)
        }
        return $summary;
    }
    /**
     * Lista todos os arquivos de log diários.
     */
    public function index(): Response
    {
        // Garante que apenas usuários autorizados possam ver os logs.
        // Você pode definir um Gate 'viewLogs' no seu AuthServiceProvider.
        // Gate::authorize('viewLogs');

        $logFiles = [];
        $files = File::glob(storage_path('logs/laravel-*.log'));

        // Ordena os arquivos do mais recente para o mais antigo
        $files = array_reverse($files);

        foreach ($files as $file) {
            preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log/', $file, $matches);
            if (isset($matches[1])) {
                $content = File::get($file);
                $logFiles[] = [
                    'date' => $matches[1],
                    'size' => File::size($file),
                    'levels' => $this->summarizeLogLevels($content), // Adiciona o resumo
                ];
            }
        }

        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '')
            ->add('Logs do Sistema', '');

        return Inertia::render('System/Logs/Index', [
            'logFiles' => $logFiles,
        ]);
    }

    /**
     * Exibe o conteúdo de um arquivo de log específico.
     */
    public function show(string $date): Response
    {
        // Gate::authorize('viewLogs');

        $filePath = storage_path("logs/laravel-{$date}.log");

        if (!File::exists($filePath)) {
            abort(404);
        }

        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Engenharia de Sistema', '')
            ->add('Logs do Sistema', route('system.logs.index'))
            ->add($date);

        return Inertia::render('System/Logs/Show', [
            'log' => [
                'date' => $date,
                'content' => $this->parseLog(File::get($filePath)),
            ]
        ]);
    }

    /**
     * Exclui um arquivo de log específico.
     */
    public function destroy(string $date)
    {
        // Gate::authorize('deleteLogs');

        $filePath = storage_path("logs/laravel-{$date}.log");

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        return redirect()->route('system.logs.index')->with('flash', [
            'type' => 'success',
            'message' => "O arquivo de log de {$date} foi excluído com sucesso.",
        ]);
    }

    /**
     * Analisa o conteúdo de um arquivo de log e o estrutura.
     * @param string $content
     * @return array
     */
    private function parseLog(string $content): array
    {
        $entries = [];
        // Regex para encontrar o início de cada entrada de log
        preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*(?:\n(?!\[\d{4}-\d{2}-\d{2}).*)*)/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $entries[] = [
                'timestamp' => $match[1],
                'env' => $match[2],
                'level' => strtolower($match[3]),
                'message' => trim($match[4]),
            ];
        }

        return array_reverse($entries); // Mostra as entradas mais recentes primeiro
    }
}
