<?php

namespace App\Http\Controllers\Api\V1\AbstractContracts\Address;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\StatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Address\StoreAddressRequest;
use App\Http\Requests\Api\V1\Address\UpdateAddressRequest;


use App\Http\Requests\Api\V1\Address\ChangeAddressIsActiveRequest;
use App\Http\Resources\Api\V1\Address\AddressResource;
use App\Models\Address;
use App\Services\QueryService;
use App\Services\TranslatorGoogle;

use App\Traits\ApiResponse;

/**
 * @version V1
 *
 * @group API-MOBILE
 *
 * @subgroup Address
 * @authenticated
 */
abstract class AddressController extends Controller
{
    use ApiResponse;
    /**
     * list Address.
     * @queryParam page int Indique o número da página desejada No-example
     * @queryParam per_page int Indique quantos registros deve conter cada página[default=50] No-example
     * @queryParam include Allowed: activeMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     * @queryParam filter[id] No-example
     * @queryParam filter[type] No-example
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
     * @queryParam filter[DeletedAt] No-example
     * @queryParam filter[DeletedAtAfter] No-example
     * @queryParam filter[DeletedAtBefore] No-example
     * @queryParam filter[DeletedAtBetween] No-example
     * @queryParam filter[DeletedAtCurrentDay] No-example
     * @queryParam filter[DeletedAtCurrentWeek] No-example
     * @queryParam filter[DeletedAtCurrentMonth] No-example
     * @queryParam filter[DeletedAtLastDays] No-example
     * @queryParam filter[DeletedAtNextDays] No-example
     * @queryParam filter[IsActive] No-example
     * @queryParam filter[IsNotActive] No-example
     * @queryParam filter[WithTrashed] No-example
     * @queryParam filter[OnlyTrashed] No-example
     * @queryParam filter[Search] No-example
     * @queryParam sort Allowed: id,is_active,created_at,updated_at,deleted_at Classifica a lista. Para classificação descendente coloque o - na frente(-id) No-example
     */

    public function index()
    {
        $query = QueryService::query(
            Address::class,
            Address::ALLOWEDFILTERS(),
            Address::ALLOWEDINCLUDES(),
            Address::ALLOWEDSORTS(),
            Address::DEFAULTSORT(),
            Address::DEFAULTINCLUDES()
        )->paginate(request('per_page'))->withQueryString();
        return AddressResource::collection($query);
    }

    /**
     * Show Address by ID.
     * @urlParam address_id int required No-example
     * @queryParam include Allowed: activeMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     */
    public function show(string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class, $addressId, Address::ALLOWEDINCLUDES(), Address::DEFAULTINCLUDES());
        if (request()->wantsJson()) {
            return  $this->sendResponse(new AddressResource($item));
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
     * Store Address.
     */
    public function store(StoreAddressRequest $request)
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
            $item = Address::create($data);

            $motive = 'Registro Inicial';
            if ($item->is_active) {
                StatusService::active($item, $motive, Auth::user()->id);
            } else {
                StatusService::deactive($item, $motive, Auth::user()->id);
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendResponse(new AddressResource($item));
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
     * Update Address by ID.
     * @urlParam address_id int required No-example
     */
    public function update(UpdateAddressRequest $request, string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class, $addressId);
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


            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendResponse(new AddressResource($item));
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
     * Delete Address by ID.
     * @urlParam address_id int required No-example
     */
    public function destroy(string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class, $addressId);
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

    /**
     * Restore Address.
     * @urlParam address_id int required No-example
     */
    public function restore($addressId)
    {
        $item = QueryService::getOrFailTrashedById(Address::class, $addressId);
        DB::beginTransaction();
        try {
            $item->restore();
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
     * Force Delete Address.
     * @urlParam address_id int required No-example
     */
    public function forceDelete($addressId)
    {
        $item = QueryService::getOrFailTrashedById(Address::class, $addressId);
        DB::beginTransaction();
        try {
            $item->forceDelete();
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
     * Active Address.
     * @urlParam address_id int required No-example
     */
    public function active(ChangeAddressIsActiveRequest $request, string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class,  $addressId);
        if ($item->is_active) {
            return  $this->sendSuccess();
        }
        DB::beginTransaction();
        try {
            $item->update([
                'is_active' => 1,
            ]);
            $motive = 'Sem motivo indicado';
            if ($request->motive) {
                $motive = $request->motive;
            }

            if ($item->is_active) {
                StatusService::active($item, $motive, Auth::user()->id);
            } else {
                StatusService::deactive($item, $motive, Auth::user()->id);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return  $this->sendSuccess();
    }

    /**
     * Deactive Address.
     * @urlParam address_id int required No-example
     */
    public function deactive(ChangeAddressIsActiveRequest $request, string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class, $addressId);
        if (!$item->is_active) {
            return  $this->sendSuccess();
        }
        DB::beginTransaction();
        try {
            $item->update([
                'is_active' => 0,
            ]);
            $motive = 'Sem motivo indicado';
            if ($request->motive) {
                $motive = $request->motive;
            }

            if ($item->is_active) {
                StatusService::active($item, $motive, Auth::user()->id);
            } else {
                StatusService::deactive($item, $motive, Auth::user()->id);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return  $this->sendSuccess();
    }

    /**
     * Toggle IsActive.
     * @urlParam gender_id int required No-example
     */
    public function toggleIsActive(string $addressId)
    {
        $item = QueryService::getOrFailById(Address::class, $addressId);

        DB::beginTransaction();
        try {
            if (!$item->is_active) {
                $item->update([
                    'is_active' => 1,
                ]);
            } else {
                $item->update([
                    'is_active' => 0,
                ]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        return  $this->sendSuccess();
    }
}
