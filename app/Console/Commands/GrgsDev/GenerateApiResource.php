<?php

namespace App\Console\Commands\GrgsDev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateApiResource extends Command
{
    protected $signature = 'grgsdev:resource {model}';
    protected $description = 'Generate API Resource based on a JSON file';

    public function handle()
    {
        $modelName = $this->argument('model');
        $jsonPath = base_path("documentation/models/json/{$modelName}.json");

        if (!File::exists($jsonPath)) {
            $this->error("JSON file for model {$modelName} not found at path {$jsonPath}.");
            return;
        }

        $jsonContent = json_decode(File::get($jsonPath), true);

        if (!$jsonContent) {
            $this->error('Invalid JSON content.');
            return;
        }

        $attributes = $jsonContent['attributes'] ?? [];
        $relations = $jsonContent['relations'] ?? [];
        $softDeletes = $jsonContent['softDeletes'] ?? false;
        $timestamps = $jsonContent['timestamps'] ?? true;
        $version = $jsonContent['version'] ?? 'V1';
        $useIsActive = $jsonContent['useIsActive'] ?? false;
        $useApprovedStatus = $jsonContent['useApprovedStatus'] ?? false;
        $logsActivity = $jsonContent['logsActivity'] ?? false;
        // Gerar API Resource
        $this->generateApiResource($modelName, $attributes, $relations, $timestamps, $softDeletes, $version, $useIsActive, $useApprovedStatus, $logsActivity);

        $this->info("API Resource for {$modelName} generated successfully.");
    }

    protected function generateApiResource($modelName, $attributes, $relations, bool $hasTimestamps = false, bool $hasSoftDeletes = false, $version, $useIsActive, $useApprovedStatus, $logsActivity)
    {
        $className = "{$modelName}Resource";
        $namespace = "App\\Http\\Resources\\Api\\{$version}\\{$modelName}";
        $filePath = app_path("Http/Resources/Api/{$version}/{$modelName}/{$className}.php");
        $hasDate = false;
        $resourceAttributes = [];
        $resourceAttributes[] = "'id' => \$this->id";
        $hasTranslated = false;
        foreach ($attributes as $attribute) {
            $type = $attribute['type'];
            $name = $attribute['name'];
            $isMultiple = $attribute['multiple'] ?? false;
            if (in_array($type, ['date', 'timestamp', 'datetime', 'time'])) {
                $hasDate = true;
                $resourceAttributes[] = "'{$name}' => \$this->formatDate(\$this->{$name})";
            } elseif (in_array($type, ['image', 'video', 'file'])) {
                if ($isMultiple) {
                    // LÓGICA CORRETA PARA MÚLTIPLOS ARQUIVOS
                    $resourceAttributes[] = "'{$name}_urls' => \$this->when(\$this->hasMedia('{$name}'), fn() => \$this->getMedia('{$name}')->map(fn(\$media) => \$media->getUrl())->toArray())";
                } else {
                    // LÓGICA CORRETA PARA ARQUIVO ÚNICO
                    $resourceAttributes[] = "'{$name}_url' => \$this->when(\$this->hasMedia('{$name}'), fn() => \$this->getFirstMediaUrl('{$name}'))";
                }
            } elseif (in_array($type, ['morphs', 'nullableMorphs'])) {
                $resourceAttributes[] = "'{$name}_id' => \$this->{$name}_id";
                $resourceAttributes[] = "'{$name}_type' => \$this->{$name}_type";
            } else {
                $resourceAttributes[] = "'{$name}' => \$this->{$name}";
                if ($attribute['translated'] ?? false) {
                    $hasTranslated = true;
                    $resourceAttributes[] = "'{$name}_translated' => \$this->getTranslations('{$name}')";
                }
            }
        }
        if ($hasTranslated) {
            $resourceAttributes[] = "'original_locale' => \$this->original_locale";
        }

        if ($useIsActive) {
            $resourceAttributes[] = "'is_active' => \$this->is_active";
            $resourceAttributes[] = "'is_active_text' => \$this->is_active_text";
        }
        if ($useApprovedStatus) {
            $resourceAttributes[] = "'approved_status' => \$this->approved_status";
            $resourceAttributes[] = "'approved_status_text' => \$this->approved_status_text";
        }

        if ($hasTimestamps) {
            $hasDate = true;
            $nameTimestamp = 'created_at';
            $resourceAttributes[] = <<<EOD
            '{$nameTimestamp}' => \$this->formatDate(\$this->{$nameTimestamp})
            EOD;
            $nameTimestamp = 'updated_at';
            $resourceAttributes[] = <<<EOD
            '{$nameTimestamp}' => \$this->formatDate(\$this->{$nameTimestamp})
            EOD;
        }
        if ($hasSoftDeletes) {
            $hasDate = true;
            $nameTimestamp = 'deleted_at';
            $resourceAttributes[] = <<<EOD
            '{$nameTimestamp}' => \$this->formatDate(\$this->{$nameTimestamp})
            EOD;
        }
        // 1. Variáveis para o Trait
        $formatsDatesImport = '';
        $formatsDatesTrait = '';

        // 2. Lógica condicional
        // Se $hasDate for verdadeiro (o que nossa lógica anterior já define), preenchemos as variáveis.
        if ($hasDate) {
            $formatsDatesImport = "use App\\Traits\\FormatsDates;";
            $formatsDatesTrait = "use FormatsDates;";
        }
        //  use App\Http\Resources\Api\V1\Example2\Example2Resource;
        $resourceImport = '';
        foreach ($relations as $relation) {
            if ($relation['type'] === 'belongsTo') {
                $resourceAttributes[] = "'{$relation['name']}' => new {$relation['related']}Resource(\$this->whenLoaded('{$relation['name']}'))";
                $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relation['related'] . '\\' . $relation['related'] . 'Resource; ';
                $resourceImport .= "\n";
            } elseif ($relation['type'] === 'belongsToMany') {
                $resourceAttributes[] = "'{$relation['name']}' => {$relation['related']}Resource::collection(\$this->whenLoaded('{$relation['name']}'))";
                $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relation['related'] . '\\' . $relation['related'] . 'Resource; ';
                $resourceImport .= "\n";
            } elseif ($relation['type'] === 'hasMany') {
                $resourceAttributes[] = "'{$relation['name']}' => {$relation['related']}Resource::collection(\$this->whenLoaded('{$relation['name']}'))";
                $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relation['related'] . '\\' . $relation['related'] . 'Resource; ';
                $resourceImport .= "\n";
            }

            //      $resourceAttributes[] = "'{$relation['name']}' => \$this->{$relation['name']}";
        }
        if ($useIsActive) {
            $relationName = 'active_motives';
            $relationWhen = 'activeMotives';
            $relationRelated = 'ActiveMotive';

            $resourceAttributes[] = "'{$relationName}' => {$relationRelated}Resource::collection(\$this->whenLoaded('{$relationWhen}'))";
            $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relationRelated . '\\' . $relationRelated . 'Resource; ';
            $resourceImport .= "\n";
        }
        if ($useApprovedStatus) {
            $relationName = 'approved_status_motives';
            $relationWhen = 'approvedMotives';

            $relationRelated = 'ApprovedStatusMotive';

            $resourceAttributes[] = "'{$relationName}' => {$relationRelated}Resource::collection(\$this->whenLoaded('{$relationWhen}'))";
            $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relationRelated . '\\' . $relationRelated . 'Resource; ';
            $resourceImport .= "\n";
        }

        if ($logsActivity) {
            $relationName = 'activities';
            $relationRelated = 'ActivityLog';

            $resourceAttributes[] = "'{$relationName}' => {$relationRelated}Resource::collection(\$this->whenLoaded('{$relationName}'))";
            $resourceImport .= 'use App\\Http\\Resources\\Api\\' . $version . '\\' . $relationRelated . '\\' . $relationRelated . 'Resource; ';
            $resourceImport .= "\n";
        }
        $carbonImport = $hasDate ? "use Carbon\Carbon;" : '';

        $resourceContent = implode(",\n            ", $resourceAttributes);

        $classTemplate = <<<EOD
<?php

namespace {$namespace};

use Illuminate\Http\Resources\Json\JsonResource;
$carbonImport
$resourceImport
$formatsDatesImport
/**
 * @version {$version}
 */
class {$className} extends JsonResource
{
    $formatsDatesTrait
    public static \$wrap = null;
    public function toArray(\$request)
    {
        return [
            {$resourceContent}
        ];
    }
}
EOD;
        // Criar diretório, se não existir
        if (!File::isDirectory(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        File::put($filePath, $classTemplate);
        $this->info("Resource {$className} gerado com sucesso.");
    }
}
