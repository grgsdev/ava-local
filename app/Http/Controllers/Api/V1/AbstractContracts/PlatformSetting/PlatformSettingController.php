<?php

namespace App\Http\Controllers\Api\V1\AbstractContracts\PlatformSetting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlatformSetting\StorePlatformSettingRequest;
use App\Http\Requests\Api\V1\PlatformSetting\UpdatePlatformSettingRequest;



use App\Http\Resources\Api\V1\PlatformSetting\PlatformSettingResource;
use App\Models\PlatformSetting;
use App\Services\QueryService;
use App\Services\TranslatorGoogle;

use App\Traits\ApiResponse;

/**
 * @version V1
 *
 * @group API-ADMINISTRATIVE
 *
 * @subgroup PlatformSetting
 * @authenticated
 */
abstract class PlatformSettingController extends Controller
{
    use ApiResponse;
    /**
     * list PlatformSetting.
     * @queryParam page int Indique o número da página desejada No-example
     * @queryParam per_page int Indique quantos registros deve conter cada página[default=50] No-example
     * @queryParam filter[id] No-example
     * @queryParam filter[CreatedAt] No-example
     * @queryParam filter[CreatedAtAfter] No-example
     * @queryParam filter[CreatedAtBefore] No-example
     * @queryParam filter[CreatedAtBetween] No-example
     * @queryParam filter[CreatedAtCurrentDay] No-example
     * @queryParam filter[CreatedAtCurrentWeek] No-example
     * @queryParam filter[CreatedAtCurrentMonth] No-example
     * @queryParam filter[CreatedAtLastDays] No-example
     * @queryParam filter[CreatedAtNextDays] No-example
     * @queryParam filter[UpdatedAt] No-example
     * @queryParam filter[UpdatedAtAfter] No-example
     * @queryParam filter[UpdatedAtBefore] No-example
     * @queryParam filter[UpdatedAtBetween] No-example
     * @queryParam filter[UpdatedAtCurrentDay] No-example
     * @queryParam filter[UpdatedAtCurrentWeek] No-example
     * @queryParam filter[UpdatedAtCurrentMonth] No-example
     * @queryParam filter[UpdatedAtLastDays] No-example
     * @queryParam filter[UpdatedAtNextDays] No-example
     * @queryParam filter[Search] No-example
     * @queryParam sort Allowed: id,created_at,updated_at Classifica a lista. Para classificação descendente coloque o - na frente(-id) No-example
     */

    public function index()
    {
        $query = QueryService::query(
            PlatformSetting::class,
            PlatformSetting::ALLOWEDFILTERS(),
            PlatformSetting::ALLOWEDINCLUDES(),
            PlatformSetting::ALLOWEDSORTS(),
            PlatformSetting::DEFAULTSORT(),
            PlatformSetting::DEFAULTINCLUDES()
        )->paginate(request('per_page'))->withQueryString();
        return PlatformSettingResource::collection($query);
    }

    /**
     * Show PlatformSetting by ID.
     * @urlParam platform_setting_id int required No-example
     *
     */
    public function show(string $platformSettingId)
    {
        $item = QueryService::getOrFailById(PlatformSetting::class, $platformSettingId, PlatformSetting::ALLOWEDINCLUDES(), PlatformSetting::DEFAULTINCLUDES());
        if (request()->wantsJson()) {
            return  $this->sendResponse(new PlatformSettingResource($item));
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
     * Store PlatformSetting.
     */
    public function store(StorePlatformSettingRequest $request)
    {
        $data = [];
        foreach ($request->all() as $key => $value) {
            if (!is_null($value)) {
                $data[$key] = $value;
            }
        }
        // TRANSLATEDS

        DB::beginTransaction();
        try {
            $item = PlatformSetting::create($data);

            // --- LÓGICA DE UPLOAD (Spatie Media Library) ---
            if ($request->hasFile('logo_web')) {
                $item->addMediaFromRequest('logo_web')->toMediaCollection('logo_web');
            }
            if ($request->hasFile('logo_mobile')) {
                $item->addMediaFromRequest('logo_mobile')->toMediaCollection('logo_mobile');
            }


            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendResponse(new PlatformSettingResource($item));
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
     * Update PlatformSetting by ID.
     * @urlParam platform_setting_id int required No-example
     */
    public function update(UpdatePlatformSettingRequest $request, string $platformSettingId)
    {
        $item = QueryService::getOrFailById(PlatformSetting::class, $platformSettingId);
        $data = [];
        foreach ($request->all() as $key => $value) {
            if (!is_null($value) && $item->{$key} !== $value) {
                $data[$key] = $value;
            }
        }

        DB::beginTransaction();
        try {
            if (!empty($data)) {
                $item->update($data);
            }

            // --- LÓGICA DE UPLOAD (Spatie Media Library) ---
            if ($request->hasFile('logo_web')) {
                $item->addMediaFromRequest('logo_web')->toMediaCollection('logo_web');
            }
            if ($request->hasFile('logo_mobile')) {
                $item->addMediaFromRequest('logo_mobile')->toMediaCollection('logo_mobile');
            }


            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendResponse(new PlatformSettingResource($item));
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
     * Delete PlatformSetting by ID.
     * @urlParam platform_setting_id int required No-example
     */
    public function destroy(string $platformSettingId)
    {
        $item = QueryService::getOrFailById(PlatformSetting::class, $platformSettingId);
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
