<?php

namespace App\Http\Controllers\Monolito\System;

use Carbon\Carbon;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\Activitylog\Models\Activity;
use App\Support\QueryBuilder\Sorts\CauserNameSort;
use App\Http\Resources\Api\V1\ActivityLog\ActivityLogResource;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all(['id', 'name']);
        $subject_types = Activity::query()->select('subject_type')->distinct()->get()->pluck('subject_type');

        $activities = QueryBuilder::for(Activity::class)
            ->allowedFilters([
                AllowedFilter::exact('causer_id'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('event'),
                // --- ALTERAÇÕES APLICADAS AQUI ---
                AllowedFilter::callback('date_from', fn($query, $value) => $query->whereDate('created_at', '>=', Carbon::parse($value))),
                AllowedFilter::callback('date_to',   fn($query, $value) => $query->whereDate('created_at', '<=', Carbon::parse($value))),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'event',
                'description',
                'subject_type',
                'created_at',
                AllowedSort::custom('causer_name', new CauserNameSort()),
            ])
            ->with(['causer', 'subject'])
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return Inertia::render('System/ActivityLog/Index', [
            'activities' => ActivityLogResource::collection($activities),
            'users' => $users,
            'subjectTypes' => $subject_types,
            'eventTypes' => ['created', 'updated', 'deleted'],
            'queryParams' => $request->all(['sort', 'filter', 'per_page']),
        ]);
    }
}
