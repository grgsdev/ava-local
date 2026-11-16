<?php

namespace App\Models\AbstractContracts;

use App\Services\DateService;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDateScopes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @version V1
 */
abstract class PlatformSetting extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasDateScopes;
    protected $dateFields = ['created_at', 'updated_at'];
    protected $cascadeDeletes = [];
    protected $fillable = [
        'platform_name',
        'contact_info',
        'social_links',
        'physical_address',

        'deleted_by_parent',
    ];

    protected $casts = [
        'platform_name' => 'string',
        'contact_info' => 'array',
        'social_links' => 'array',
        'physical_address' => 'string',
        'deleted_by_parent' => 'boolean',
    ];

    // APPENDS
    protected $appends = [];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo_web')
            ->singleFile();

        $this->addMediaCollection('logo_mobile')
            ->singleFile();
    }


    // SCOPES




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


    // QUERY BUILDER

    /**
     * The allowed filters attributes.
     * id,CreatedAt,CreatedAtBefore,CreatedAtAfter,CreatedAtBetween,CreatedAtCurrentDay,CreatedAtCurrentWeek,CreatedAtCurrentMonth,CreatedAtLastDays,CreatedAtNextDays,UpdatedAt,UpdatedAtBefore,UpdatedAtAfter,UpdatedAtBetween,UpdatedAtCurrentDay,UpdatedAtCurrentWeek,UpdatedAtCurrentMonth,UpdatedAtLastDays,UpdatedAtNextDays
     */
    public static function ALLOWEDFILTERS()
    {
        return [
            AllowedFilter::exact('id'),
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
        ];
    }

    /**
     * The allowed includes relationships.
     * No includes relationships found.
     */
    public static function ALLOWEDINCLUDES()
    {
        return [];
    }

    /**
     * The allowed sorts attributes.
     * id,created_at,updated_at
     */
    public static function ALLOWEDSORTS()
    {
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * The Default sorts attributes.
     * No default sortable attributes found.
     */
    public static function DEFAULTSORT()
    {
        return [];
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
