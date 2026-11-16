<?php

namespace App\Http\Controllers\Api\V1\AbstractContracts\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\StatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;


use App\Http\Requests\Api\V1\User\ChangeUserIsActiveRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\QueryService;
use App\Services\TranslatorGoogle;

use App\Traits\ApiResponse;

/**
 * @version V1
 *
 * @group API-MOBILE
 *
 * @subgroup User
 * @authenticated
 */
abstract class UserController extends Controller
{
    use ApiResponse;
    /**
     * list User.
     * @queryParam page int Indique o número da página desejada No-example
     * @queryParam per_page int Indique quantos registros deve conter cada página[default=50] No-example
     * @queryParam include Allowed: activeMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     * @queryParam filter[id] No-example
     * @queryParam filter[name] No-example
     * @queryParam filter[email] No-example
     * @queryParam filter[nickname] No-example
     * @queryParam filter[username] No-example
     * @queryParam filter[phone_number] No-example
     * @queryParam filter[BirthDate] No-example
     * @queryParam filter[BirthDateAfter] No-example
     * @queryParam filter[BirthDateBefore] No-example
     * @queryParam filter[BirthDateBetween] No-example
     * @queryParam filter[BirthDateCurrentDay] No-example
     * @queryParam filter[BirthDateCurrentWeek] No-example
     * @queryParam filter[BirthDateCurrentMonth] No-example
     * @queryParam filter[BirthDateLastDays] No-example
     * @queryParam filter[BirthDateNextDays] No-example
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
     * @queryParam sort Allowed: id,name,email,nickname,username,phone_number,birth_date,is_active,created_at,updated_at,deleted_at Classifica a lista. Para classificação descendente coloque o - na frente(-id) No-example
     */

    public function index()
    {
        $query = QueryService::query(
            User::class,
            User::ALLOWEDFILTERS(),
            User::ALLOWEDINCLUDES(),
            User::ALLOWEDSORTS(),
            User::DEFAULTSORT(),
            User::DEFAULTINCLUDES()
        )->paginate(request('per_page'))->withQueryString();
        return UserResource::collection($query);
    }

    /**
     * Show User by ID.
     * @urlParam user_id int required No-example
     * @queryParam include Allowed: activeMotives,activities Relacionamentos que podem ser incluídos na resposta No-example
     */
    public function show(string $userId)
    {
        $item = QueryService::getOrFailById(User::class, $userId, User::ALLOWEDINCLUDES(), User::DEFAULTINCLUDES());
        if (request()->wantsJson()) {
            return  $this->sendResponse(new UserResource($item));
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
     * Store User.
     */
    public function store(StoreUserRequest $request)
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
            $item = User::create($data);

            // --- LÓGICA DE UPLOAD (Spatie Media Library) ---
            if ($request->hasFile('avatar')) {
                $item->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }

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
            return  $this->sendResponse(new UserResource($item));
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
     * Update User by ID.
     * @urlParam user_id int required No-example
     */
    public function update(UpdateUserRequest $request, string $userId)
    {
        $item = QueryService::getOrFailById(User::class, $userId);
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
            if ($request->hasFile('avatar')) {
                $item->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }


            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
        if (request()->wantsJson()) {
            return  $this->sendResponse(new UserResource($item));
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
     * Delete User by ID.
     * @urlParam user_id int required No-example
     */
    public function destroy(string $userId)
    {
        $item = QueryService::getOrFailById(User::class, $userId);
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
     * Restore User.
     * @urlParam user_id int required No-example
     */
    public function restore($userId)
    {
        $item = QueryService::getOrFailTrashedById(User::class, $userId);
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
     * Force Delete User.
     * @urlParam user_id int required No-example
     */
    public function forceDelete($userId)
    {
        $item = QueryService::getOrFailTrashedById(User::class, $userId);
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
     * Active User.
     * @urlParam user_id int required No-example
     */
    public function active(ChangeUserIsActiveRequest $request, string $userId)
    {
        $item = QueryService::getOrFailById(User::class,  $userId);
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
     * Deactive User.
     * @urlParam user_id int required No-example
     */
    public function deactive(ChangeUserIsActiveRequest $request, string $userId)
    {
        $item = QueryService::getOrFailById(User::class, $userId);
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
    public function toggleIsActive(string $userId)
    {
        $item = QueryService::getOrFailById(User::class, $userId);

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
