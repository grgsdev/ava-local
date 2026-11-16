<?php

namespace App\Http\Controllers\Monolito\System;

use Inertia\Inertia;
use App\Models\CommandLog;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use App\Services\QueryService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Http\Resources\Api\V1\CommandLog\CommandLogResource;
use Illuminate\Http\Request;

class CommandLogController extends BaseController
{

    /**
     * list Log Scheduled Commands.
     * @queryParam page int Indique o número da página desejada No-example
     * @queryParam per_page int Indique quantos registros deve conter cada página[default=50] No-example
     * @queryParam filter[command] No-example
     * @queryParam filter[status] No-example
     * @queryParam filter[ExecutedAt] No-example
     * @queryParam filter[ExecutedAtAfter] No-example
     * @queryParam filter[ExecutedAtBefore] No-example
     * @queryParam filter[ExecutedAtBetween] No-example
     * @queryParam filter[Search] No-example
     * @queryParam sort Allowed: executed_at Classifica a lista. Para classificação descendente coloque o - na frente(-id) No-example
     */
    public function index(Request $request)
    {
        $query = QueryService::query(
            CommandLog::class,
            CommandLog::ALLOWEDFILTERS(),
            CommandLog::ALLOWEDINCLUDES(),
            CommandLog::ALLOWEDSORTS(),
            CommandLog::DEFAULTSORT()
        )->paginate(request('per_page', 15))->withQueryString();
        $this->breadcrumbsManager->add('Dashboard', route('dashboard'))
            ->add('Gerenciamento de Comandos', '');
        if (request()->wantsJson()) {
            return  CommandLogResource::collection($query);
        } else {
            return Inertia::render('System/CommandLogs/Index', [
                'commands' => CommandLogResource::collection($query),
                'queryParams' => $request->all(['sort', 'filter', 'per_page']),

            ]);
        }
    }


    /**
     * Show Log Scheduled Command by ID.
     * @urlParam id int required No-example
     */
    public function show($id)
    {
        $item = QueryService::getOrFailById(CommandLog::class, $id, CommandLog::ALLOWEDINCLUDES());
        if (request()->wantsJson()) {
            return  $this->sendResponse(new CommandLogResource($item));
        } else {
            return Inertia::render('System/CommandLogs/Show', [
                'command' => new CommandLogResource($item),
            ]);
        }
    }

    /**
     * Delete Log Scheduled Command by ID.
     * @urlParam command_log_id int required No-example
     */
    public function destroy($id)
    {
        $item = QueryService::getOrFailById(CommandLog::class, $id);
        DB::beginTransaction();
        try {
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
}
