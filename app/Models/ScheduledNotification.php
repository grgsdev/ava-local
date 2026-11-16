<?php

namespace App\Models;

use App\Services\DateService;
use App\Traits\HasDateScopes;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledNotification extends Model
{
    use HasFactory;
    use LogsActivity;

    use HasDateScopes;
    protected $dateFields = ['created_at', 'updated_at', 'start_date'];
    const IS_ACTIVE_TRUE = '1';
    const IS_ACTIVE_FALSE = '0';
    const IS_ACTIVE_TEXT = ['0' => 'INACTIVE', '1' => 'ACTIVE'];

    protected $fillable = [
        'title',
        'body',
        'target_roles',
        'delivery_channels',
        'start_date',
        'period_in_days',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'delivery_channels' => 'array',
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    // APPENDS
    protected $appends = [
        'is_active_text'
    ];

    public function getIsActiveTextAttribute(): string
    {
        // 1. Convertemos o valor para inteiro. Se for nulo ou inválido, torna-se 0.
        $key = (int) $this->is_active;

        // 2. Verificamos se a chave (0 ou 1) existe no nosso array antes de a usar.
        //    Se não existir, devolvemos um texto padrão.
        return __(self::IS_ACTIVE_TEXT[$key] ?? 'Desconhecido');
    }

    // SCOPES

    public function scopeSearch(Builder $builder, string $search)
    {
        $parsedDate = DateService::parseDate($search);

        if ($parsedDate) {
            $builder->where(function ($query) use ($parsedDate) {
                $query->whereDate('created_at', $parsedDate->format('Y-m-d'));
                $query->whereDate('updated_at', $parsedDate->format('Y-m-d'));
                $query->whereDate('deleted_at', $parsedDate->format('Y-m-d'));
            });
        } else {
            $builder->where(function ($query) use ($search) {
                // Busca parcial de datas (exemplo: dia/mês ou apenas mês)
                if (preg_match('/^\d{1,2}\/\d{1,2}$/', $search)) {
                    // Exemplo: 29/03 (dia/mês)
                    list($day, $month) = explode('/', $search);
                    $query->whereMonth('created_at', $month)
                        ->whereDay('created_at', $day);
                    $query->whereMonth('updated_at', $month)
                        ->whereDay('updated_at', $day);
                    $query->whereMonth('deleted_at', $month)
                        ->whereDay('deleted_at', $day);
                } elseif (preg_match('/^\d{1,2}\/\$/', $search)) {
                    // Exemplo: 29/ (somente dia)
                    $day = str_replace('/', '', $search);
                    $query->whereDay('created_at', $day);
                    $query->whereDay('updated_at', $day);
                    $query->whereDay('deleted_at', $day);
                } elseif (preg_match('/^\/\d{1,2}$/', $search)) {
                    // Exemplo: /05 (somente mês)
                    $month = str_replace('/', '', $search);
                    $query->whereMonth('created_at', $month);
                    $query->whereMonth('updated_at', $month);
                    $query->whereMonth('deleted_at', $month);
                } else {
                    $query->orWhere('title', 'LIKE', "%$search%");
                }
            });
        }
    }

    public function scopeIsActive(Builder $query): void
    {
        $query->where('is_active', 1);
    }


    public function scopeIsNotActive(Builder $query): void
    {
        $query->where('is_active', 0);
    }

    public function scopeCreatedAt(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'created_at', 'At', $date);
    }

    public function scopeCreatedAtBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtBefore', $date);
    }

    public function scopeCreatedAtAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtAfter', $date);
    }

    public function scopeCreatedAtBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtBetween', $startDate, $endDate);
    }

    public function scopeCreatedAtCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtCurrentDay');
    }

    public function scopeCreatedAtCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtCurrentWeek');
    }

    public function scopeCreatedAtCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtCurrentMonth');
    }

    public function scopeCreatedAtLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtLastDays', $days);
    }

    public function scopeCreatedAtNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'created_at', 'AtNextDays', $days);
    }

    public function scopeUpdatedAt(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'updated_at', 'At', $date);
    }

    public function scopeUpdatedAtBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtBefore', $date);
    }

    public function scopeUpdatedAtAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtAfter', $date);
    }

    public function scopeUpdatedAtBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtBetween', $startDate, $endDate);
    }

    public function scopeUpdatedAtCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtCurrentDay');
    }

    public function scopeUpdatedAtCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtCurrentWeek');
    }

    public function scopeUpdatedAtCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtCurrentMonth');
    }

    public function scopeUpdatedAtLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtLastDays', $days);
    }

    public function scopeUpdatedAtNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'updated_at', 'AtNextDays', $days);
    }
    /////////////////////////////// Start Date Scopes \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    public function scopeStartDate(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'start_date', 'At', $date);
    }

    public function scopeStartDateBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtBefore', $date);
    }

    public function scopeStartDateAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtAfter', $date);
    }

    public function scopeStartDateBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtBetween', $startDate, $endDate);
    }

    public function scopeStartDateCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtCurrentDay');
    }

    public function scopeStartDateCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtCurrentWeek');
    }

    public function scopeStartDateCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtCurrentMonth');
    }

    public function scopeStartDateLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtLastDays', $days);
    }

    public function scopeStartDateNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'start_date', 'AtNextDays', $days);
    }

    // QUERY BUILDER

    /**
     * The allowed filters attributes.
     * id,name,is_active,is_premium_only,IsPremiumOnly,NotIsPremiumOnly,IsActive,IsNotActive,CreatedAt,CreatedAtBefore,CreatedAtAfter,CreatedAtBetween,CreatedAtCurrentDay,CreatedAtCurrentWeek,CreatedAtCurrentMonth,CreatedAtLastDays,CreatedAtNextDays,UpdatedAt,UpdatedAtBefore,UpdatedAtAfter,UpdatedAtBetween,UpdatedAtCurrentDay,UpdatedAtCurrentWeek,UpdatedAtCurrentMonth,UpdatedAtLastDays,UpdatedAtNextDays,DeletedAt,DeletedAtBefore,DeletedAtAfter,DeletedAtBetween,DeletedAtCurrentDay,DeletedAtCurrentWeek,DeletedAtCurrentMonth,DeletedAtLastDays,DeletedAtNextDays,WithTrashed,OnlyTrashed,Search
     */
    public static function ALLOWEDFILTERS()
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('title'),
            AllowedFilter::exact('is_active'),
            AllowedFilter::scope('IsActive'),
            AllowedFilter::scope('IsNotActive'),
            AllowedFilter::scope('CreatedAt'),
            AllowedFilter::scope('CreatedAtBefore'),
            AllowedFilter::scope('CreatedAtAfter'),
            AllowedFilter::scope('CreatedAtBetween'),
            AllowedFilter::scope('CreatedAtCurrentDay'),
            AllowedFilter::scope('CreatedAtCurrentWeek'),
            AllowedFilter::scope('CreatedAtCurrentMonth'),
            AllowedFilter::scope('CreatedAtLastDays'),
            AllowedFilter::scope('CreatedAtNextDays'),
            AllowedFilter::scope('UpdatedAt'),
            AllowedFilter::scope('UpdatedAtBefore'),
            AllowedFilter::scope('UpdatedAtAfter'),
            AllowedFilter::scope('UpdatedAtBetween'),
            AllowedFilter::scope('UpdatedAtCurrentDay'),
            AllowedFilter::scope('UpdatedAtCurrentWeek'),
            AllowedFilter::scope('UpdatedAtCurrentMonth'),
            AllowedFilter::scope('UpdatedAtLastDays'),
            AllowedFilter::scope('UpdatedAtNextDays'),
            AllowedFilter::scope('StartDate'),
            AllowedFilter::scope('StartDateBefore'),
            AllowedFilter::scope('StartDateAfter'),
            AllowedFilter::scope('StartDateBetween'),
            AllowedFilter::scope('StartDateCurrentDay'),
            AllowedFilter::scope('StartDateCurrentWeek'),
            AllowedFilter::scope('StartDateCurrentMonth'),
            AllowedFilter::scope('StartDateLastDays'),
            AllowedFilter::scope('StartDateNextDays'),

            AllowedFilter::scope('Search'),

        ];
    }

    /**
     * The allowed includes relationships.
     * no includes available
     */
    public static function ALLOWEDINCLUDES()
    {
        return [];
    }

    /**
     * The allowed sorts attributes.
     * id,title,start_date,is_active,created_at,updated_at
     */
    public static function ALLOWEDSORTS()
    {
        return ['id', 'title',  'start_date', 'is_active', 'created_at', 'updated_at'];
    }

    /**
     * The Default sorts attributes.
     * -start_date,title
     */
    public static function DEFAULTSORT()
    {
        return ['-start_date', 'title'];
    }

    /**
     * The default includes relationships.
     * No default includes relationships found.
     */
    public static function DEFAULTINCLUDES()
    {
        return [];
    }


    // ACTYVITY LOGS
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->dontSubmitEmptyLogs();
    }
}
