<?php

namespace App\Models\AbstractContracts;

use App\Services\DateService;
use App\Traits\HasDateScopes;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class CommandLog extends Model
{
    use HasDateScopes;
    protected $dateFields = ['executed_at'];

    protected $table = 'command_logs';

    // Atributos que podem ser atribuídos em massa
    protected $fillable = [
        'command',
        'output',
        'status',
        'executed_at',
    ];
    protected $casts = [
        'executed_at' => 'date',
        'status' => 'boolean',
    ];
    //SCOPES
    public function scopeSearch(Builder $builder, string $search)
    {
        $parsedDate = DateService::parseDate($search);

        if ($parsedDate) {
            $builder->where(function ($query) use ($parsedDate) {
                $query->whereDate('executed_at', $parsedDate->format('Y-m-d'));
            });
        } else {
            $builder->where(function ($query) use ($search) {
                // Busca parcial de datas (exemplo: dia/mês ou apenas mês)
                if (preg_match('/^\d{1,2}\/\d{1,2}$/', $search)) {
                    // Exemplo: 29/03 (dia/mês)
                    list($day, $month) = explode('/', $search);
                    $query->whereMonth('executed_at', $month)
                        ->whereDay('executed_at', $day);
                } elseif (preg_match('/^\d{1,2}\/\$/', $search)) {
                    // Exemplo: 29/ (somente dia)
                    $day = str_replace('/', '', $search);
                    $query->whereDay('executed_at', $day);
                } elseif (preg_match('/^\/\d{1,2}$/', $search)) {
                    // Exemplo: /05 (somente mês)
                    $month = str_replace('/', '', $search);
                    $query->whereMonth('executed_at', $month);
                } else {
                    $query->orWhere('command', 'LIKE', "%$search%");
                }
            });
        }
    }

    public function scopeExecutedAt(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'executed_at', 'At', $date);
    }

    public function scopeExecutedAtBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtBefore', $date);
    }

    public function scopeExecutedAtAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtAfter', $date);
    }

    public function scopeExecutedAtBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtBetween', $startDate, $endDate);
    }

    public function scopeExecutedAtCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtCurrentDay');
    }

    public function scopeExecutedAtCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtCurrentWeek');
    }

    public function scopeExecutedAtCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtCurrentMonth');
    }

    public function scopeExecutedAtLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtLastDays', $days);
    }

    public function scopeExecutedAtNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'executed_at', 'AtNextDays', $days);
    }



    // QUERY BUILDER

    /**
     * The allowed filters attributes.
     * command,status,ExecutedAt,ExecutedAtBefore,ExecutedAtAfter,ExecutedAtBetween,Search
     */
    public static function ALLOWEDFILTERS()
    {
        return [
            AllowedFilter::partial('command'),
            AllowedFilter::exact('status'),

            AllowedFilter::scope('ExecutedAt'),
            AllowedFilter::scope('ExecutedAtBefore'),
            AllowedFilter::scope('ExecutedAtAfter'),
            AllowedFilter::scope('ExecutedAtBetween'),
            AllowedFilter::scope('Search'),

        ];
    }

    /**
     * The allowed includes attributes.
     *
     */
    public static function ALLOWEDINCLUDES()
    {
        return [];
    }

    /**
     * The allowed sorts attributes.
     * command,status,executed_at
     */
    public static function ALLOWEDSORTS()
    {
        return ['command', 'status', 'executed_at'];
    }

    /**
     * The Default sorts attributes.
     * executed_at
     */
    public static function DEFAULTSORT()
    {
        return ['-executed_at'];
    }
}
