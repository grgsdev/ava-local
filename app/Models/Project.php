<?php

namespace App\Models;

use App\Services\DateService;
use App\Traits\HasDateScopes;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Activity;

class Project extends Model
{
    public const STATUS_DRAFT = 'Em Edição';
    public const STATUS_PENDING_APPROVAL = 'Aguardando aprovação';
    public const STATUS_REJECTED = 'Reprovado';
    public const STATUS_APPROVED = 'Aprovado';

    use HasFactory, SoftDeletes;
    use HasDateScopes;
    protected $dateFields = ['created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'company_id',
        'user_id',
        'course_id',
        'title',
        'type',
        'status',
    ];

    /**
     * Define a relação de que um Projeto (Project) pertence a uma Empresa (Company).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Define a relação de que um Projeto (Project) pertence a um Utilizador (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function approvalHistory(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')
            ->where('log_name', 'project_status')
            ->latest('id');
    }
    public function publications(): HasMany { return $this->hasMany(CoursePublication::class); }
    public function activePublication(): HasOne { return $this->hasOne(CoursePublication::class)->where('state', CoursePublication::ACTIVE); }

    /**
     * Define a relação: um Projeto pode ter muitos Vídeos.
     */
    public function videos()
    {
        return $this->hasMany(Video::class);
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


    public function scopeDeletedAt(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'At', $date);
    }

    public function scopeDeletedAtBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtBefore', $date);
    }

    public function scopeDeletedAtAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtAfter', $date);
    }

    public function scopeDeletedAtBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtBetween', $startDate, $endDate);
    }

    public function scopeDeletedAtCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtCurrentDay');
    }

    public function scopeDeletedAtCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtCurrentWeek');
    }

    public function scopeDeletedAtCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtCurrentMonth');
    }

    public function scopeDeletedAtLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtLastDays', $days);
    }

    public function scopeDeletedAtNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'deleted_at', 'AtNextDays', $days);
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




    public function scopeWithTrashed($query)
    {
        return $query->withTrashed();
    }

    public function scopeOnlyTrashed($query)
    {
        return $query->onlyTrashed();
    }

    // QUERY BUILDER

    /**
     * The allowed filters attributes.
     * id,name,email,nickname,username,is_active,phone_number,BirthDate,BirthDateBefore,BirthDateAfter,BirthDateBetween,BirthDateCurrentDay,BirthDateCurrentWeek,BirthDateCurrentMonth,BirthDateLastDays,BirthDateNextDays,IsActive,IsNotActive,CreatedAt,CreatedAtBefore,CreatedAtAfter,CreatedAtBetween,CreatedAtCurrentDay,CreatedAtCurrentWeek,CreatedAtCurrentMonth,CreatedAtLastDays,CreatedAtNextDays,UpdatedAt,UpdatedAtBefore,UpdatedAtAfter,UpdatedAtBetween,UpdatedAtCurrentDay,UpdatedAtCurrentWeek,UpdatedAtCurrentMonth,UpdatedAtLastDays,UpdatedAtNextDays,DeletedAt,DeletedAtBefore,DeletedAtAfter,DeletedAtBetween,DeletedAtCurrentDay,DeletedAtCurrentWeek,DeletedAtCurrentMonth,DeletedAtLastDays,DeletedAtNextDays,WithTrashed,OnlyTrashed,Search
     */
    public static function ALLOWEDFILTERS()
    {
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('title'),
            AllowedFilter::partial('type'),
            AllowedFilter::exact('status'),
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
            AllowedFilter::scope('DeletedAt'),
            AllowedFilter::scope('DeletedAtBefore'),
            AllowedFilter::scope('DeletedAtAfter'),
            AllowedFilter::scope('DeletedAtBetween'),
            AllowedFilter::scope('DeletedAtCurrentDay'),
            AllowedFilter::scope('DeletedAtCurrentWeek'),
            AllowedFilter::scope('DeletedAtCurrentMonth'),
            AllowedFilter::scope('DeletedAtLastDays'),
            AllowedFilter::scope('DeletedAtNextDays'),
            AllowedFilter::scope('WithTrashed'),
            AllowedFilter::scope('OnlyTrashed'),

            AllowedFilter::scope('Search'),

        ];
    }

    /**
     * The allowed includes relationships.
     * company,users,videos
     */
    public static function ALLOWEDINCLUDES()
    {
        return ['company', 'users', 'videos'];
    }

    /**
     * The allowed sorts attributes.
     * id,title,status,type,created_at,updated_at,deleted_at
     */
    public static function ALLOWEDSORTS()
    {
        return ['id', 'title', 'status', 'type', 'created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * The Default sorts attributes.
     * created_at,title
     */
    public static function DEFAULTSORT()
    {
        return ['-created_at', 'title'];
    }

    /**
     * The default includes relationships.
     * No default includes relationships found.
     */
    public static function DEFAULTINCLUDES()
    {
        return [];
    }
}
