<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\DeletedRecord;
use App\Services\QueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\BaseController;
use App\Http\Resources\Api\V1\DeletedRecord\DeletedRecordResource;
use Illuminate\Database\QueryException;

class DeletedRecordController extends BaseController
{

    /**
     * list Deleteds records.
     * @queryParam page int Indique o número da página desejada No-example
     * @queryParam per_page int Indique quantos registros deve conter cada página[default=50] No-example
     * @queryParam include Allowed: activeMotives,approvedMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     * @queryParam filter[DeletedAt] No-example
     * @queryParam filter[DeletedAtAfter] No-example
     * @queryParam filter[DeletedAtBefore] No-example
     * @queryParam filter[DeletedAtBetween] No-example
     * @queryParam filter[CauserName] No-example
     * @queryParam filter[Search] No-example
     * @queryParam sort Allowed: deleted_at Classifica a lista. Para classificação descendente coloque o - na frente(-id) No-example
     */
    public function index(Request $request)
    {
        $query = QueryService::query(
            DeletedRecord::class,
            DeletedRecord::ALLOWEDFILTERS(),
            DeletedRecord::ALLOWEDINCLUDES(),
            DeletedRecord::ALLOWEDSORTS(),
            DeletedRecord::DEFAULTSORT()
        )->paginate(request('per_page', 15))->withQueryString();
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Gerenciamento de Comandos', '');
        if (request()->wantsJson()) {
            return  DeletedRecordResource::collection($query);
        } else {
            return Inertia::render('System/DeletedRecords/Index', [
                'records' => DeletedRecordResource::collection($query),
                'queryParams' => $request->all(['sort', 'filter', 'per_page']),

            ]);
        }
    }

    /**
     * Show Deleted Record by ID.
     * @urlParam deleted_record_id int required No-example
     * @queryParam include Allowed: activeMotives,approvedMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     */

    public function show($id)
    {
        $item = QueryService::getOrFailById(DeletedRecord::class, $id, DeletedRecord::ALLOWEDINCLUDES());

        if (request()->wantsJson()) {
            return  $this->sendResponse(new DeletedRecordResource($item));
        } else {
            return Inertia::render('System/DeletedRecords/Show', [
                'record' => new DeletedRecordResource($item),
            ]);
        }
    }


    /**
     * Delete Log Deleted Record by ID.
     * @urlParam deleted_record_id int required No-example
     */
    public function destroy($id)
    {
        $item = QueryService::getOrFailById(DeletedRecord::class, $id);
        DB::beginTransaction();
        try {
            $otherDeleteds = DeletedRecord::where('subject_type', $item->subject_type)
                ->where('subject_id', $item->subject_id)
                ->where('id', '<>', $item->subject_id)
                ->get();
            foreach ($otherDeleteds as $other) {
                $other->delete();
            }
            $item->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendSuccess();
        } else {
            return redirect()->back()->with(
                'flash',
                [
                    'id' => Str::uuid(),
                    'type' => 'success',
                    'message' => __('The action was executed successfully.'),
                ]
            );
        }
    }

    /**
     * Recover Deleted physical record.
     * @urlParam deleted_record_id int required No-example
     */

    public function recoverData($id)
    {
        // Usamos a transação, mas com um try...catch interno para logging
        try {
            return DB::transaction(function () use ($id) {
                $deletedRecord = DeletedRecord::findOrFail($id);
                $originalData = json_decode($deletedRecord->subject_data, true);
                $modelClass = $deletedRecord->subject_type;

                if (!class_exists($modelClass)) {
                    return redirect()->back()->with('flash', [
                        'type' => 'error',
                        'message' => 'A classe do modelo original não foi encontrada.',
                    ]);
                }

                $modelInstance = new $modelClass;
                $appends = $modelInstance->getAppends();
                $filteredData = collect($originalData)->except($appends)->toArray();
                $originalId = $deletedRecord->subject_id;

                // Prepara os dados para restauração
                if (Schema::hasColumn($modelInstance->getTable(), 'deleted_at')) {
                    $filteredData['deleted_at'] = null;
                }
                if (Schema::hasColumn($modelInstance->getTable(), 'password')) {
                    $filteredData['password'] = Hash::make(Str::random(10)); // Gera uma senha aleatória segura
                }
                // Garante que o estado 'deleted_by_parent' seja resetado
                if (Schema::hasColumn($modelInstance->getTable(), 'deleted_by_parent')) {
                    $filteredData['deleted_by_parent'] = false;
                }

                // --- LÓGICA SIMPLIFICADA E SEGURA ---
                // Usa updateOrCreate para evitar erros de chave duplicada.
                // Ele vai procurar um registro com o ID original. Se encontrar, atualiza. Se não, cria.
                $modelClass::updateOrCreate(
                    ['id' => $originalId], // Condição para encontrar o registro
                    $filteredData           // Dados para atualizar ou criar
                );

                // Exclui o registro de backup e outros duplicados
                DeletedRecord::where('subject_type', $deletedRecord->subject_type)
                    ->where('subject_id', $deletedRecord->subject_id)
                    ->delete();

                return redirect()->back()->with('flash', [
                    'type' => 'success',
                    'message' => 'Registro recuperado com sucesso.',
                ]);
            });
        } catch (QueryException $e) {
            // Captura erros de banco de dados (ex: violação de chave estrangeira)
            Log::error('Falha ao recuperar registro (QueryException): ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Erro de banco de dados ao tentar recuperar o registro. Verifique os logs.'
            ]);
        } catch (\Throwable $th) {
            // Captura qualquer outro erro
            Log::error('Falha inesperada ao recuperar registro: ' . $th->getMessage());
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'Ocorreu um erro inesperado. O administrador foi notificado.'
            ]);
        }
    }
}
