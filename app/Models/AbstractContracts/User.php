<?php

namespace App\Models\AbstractContracts;


use App\Models\CheckIn;
use App\Models\Company;

use App\Traits\Addressable;
use App\Services\DateService;
use App\Traits\HasDateScopes;

use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use App\Traits\CascadeSoftDeletes;
use Spatie\Activitylog\LogOptions;
use App\Traits\ClearsResponseCache;
use Spatie\Permission\Traits\HasRoles;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\AccountDeletionNotification;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @version V1
 */
abstract class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasRoles;
    use HasApiTokens;
    use SoftDeletes;
    use CascadeSoftDeletes;
    use LogsActivity;
    use ClearsResponseCache;
    use InteractsWithMedia, Cachable;
    use Addressable;
    const IS_ACTIVE_TRUE = '1';
    const IS_ACTIVE_FALSE = '0';
    const IS_ACTIVE_TEXT = ['0' => 'INACTIVE', '1' => 'ACTIVE'];




    use HasDateScopes;
    protected $dateFields = ['created_at', 'updated_at'];
    protected $cascadeDeletes = [];


    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'nickname',
        'username',
        'pin_code',
        'password',
        'remember_token',
        'phone_number',
        'birth_date',
        'cpf',
        'cnpj',
        'is_active',
        'send_notification',
        'send_email',
        'deleted_by_parent',
        'verification_code',
        'verification_sent_at',
        'verification_resend_count',
        'timezone',
    ];
    protected $hidden = [
        'password',
        'remember_token',
        'pin_code'
    ];
    protected $casts = [
        'name' => 'string',
        'email' => 'string',
        'email_verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'nickname' => 'string',
        'username' => 'string',
        'pin_code' => 'hashed',
        'password' => 'hashed',
        'remember_token' => 'string',
        'phone_number' => 'string',
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'send_notification' => 'boolean',
        'send_email' => 'boolean',
        'deleted_by_parent' => 'boolean',
        'timezone' => 'string',
    ];

    /**
     * Envia a notificação de exclusão de conta.
     */
    public function sendAccountDeletionLinkNotification(string $token): void
    {
        $this->notify(new AccountDeletionNotification($token));
    }

    // APPENDS
    protected $appends = [
        'is_active_text',
    ];

    public function getIsActiveTextAttribute()
    {
        // 1. Convertemos o valor para inteiro. Se for nulo ou inválido, torna-se 0.
        $key = (int) $this->is_active;

        // 2. Verificamos se a chave (0 ou 1) existe no nosso array antes de a usar.
        //    Se não existir, devolvemos um texto padrão.
        return __(self::IS_ACTIVE_TEXT[$key] ?? 'Desconhecido');
    }




    // INCLUDES
    public function companies()
    {
        return $this->belongsToMany(Company::class)->withPivot('role', 'status');
    }

    /**
     * Retorna todos os check-ins feitos por este usuário.
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }


    // SCOPES

    public function scopeSearch(Builder $builder, string $search)
    {
        $parsedDate = DateService::parseDate($search);

        if ($parsedDate) {
            $builder->where(function ($query) use ($parsedDate) {
                $query->whereDate('birth_date', $parsedDate->format('Y-m-d'));
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
                    $query->whereMonth('birth_date', $month)
                        ->whereDay('birth_date', $day);
                    $query->whereMonth('created_at', $month)
                        ->whereDay('created_at', $day);
                    $query->whereMonth('updated_at', $month)
                        ->whereDay('updated_at', $day);
                    $query->whereMonth('deleted_at', $month)
                        ->whereDay('deleted_at', $day);
                } elseif (preg_match('/^\d{1,2}\/\$/', $search)) {
                    // Exemplo: 29/ (somente dia)
                    $day = str_replace('/', '', $search);
                    $query->whereDay('birth_date', $day);
                    $query->whereDay('created_at', $day);
                    $query->whereDay('updated_at', $day);
                    $query->whereDay('deleted_at', $day);
                } elseif (preg_match('/^\/\d{1,2}$/', $search)) {
                    // Exemplo: /05 (somente mês)
                    $month = str_replace('/', '', $search);
                    $query->whereMonth('birth_date', $month);
                    $query->whereMonth('created_at', $month);
                    $query->whereMonth('updated_at', $month);
                    $query->whereMonth('deleted_at', $month);
                } else {
                    $query->orWhere('name', 'LIKE', "%$search%");
                    $query->orWhere('email', 'LIKE', "%$search%");
                    $query->orWhere('nickname', 'LIKE', "%$search%");
                    $query->orWhere('username', 'LIKE', "%$search%");
                    $query->orWhere('phone_number', 'LIKE', "%$search%");
                }
            });
        }
    }


    public function scopeIsActive(Builder $query): void
    {
        $query->where('is_active', true);
    }


    public function scopeIsNotActive(Builder $query): void
    {
        $query->where('is_active', false);
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

    public function scopeEmailVerifiedAt(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'At', $date);
    }

    public function scopeEmailVerifiedAtBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtBefore', $date);
    }

    public function scopeEmailVerifiedAtAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtAfter', $date);
    }

    public function scopeEmailVerifiedAtBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtBetween', $startDate, $endDate);
    }

    public function scopeEmailVerifiedAtCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtCurrentDay');
    }

    public function scopeEmailVerifiedAtCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtCurrentWeek');
    }

    public function scopeEmailVerifiedAtCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtCurrentMonth');
    }

    public function scopeEmailVerifiedAtLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtLastDays', $days);
    }

    public function scopeEmailVerifiedAtNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'email_verified_at', 'AtNextDays', $days);
    }

    public function scopeBirthDate(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'birth_date', 'At', $date);
    }

    public function scopeBirthDateBefore(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtBefore', $date);
    }

    public function scopeBirthDateAfter(Builder $builder, $date)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtAfter', $date);
    }

    public function scopeBirthDateBetween(Builder $builder, $startDate, $endDate)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtBetween', $startDate, $endDate);
    }

    public function scopeBirthDateCurrentDay(Builder $builder)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtCurrentDay');
    }

    public function scopeBirthDateCurrentWeek(Builder $builder)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtCurrentWeek');
    }

    public function scopeBirthDateCurrentMonth(Builder $builder)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtCurrentMonth');
    }

    public function scopeBirthDateLastDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtLastDays', $days);
    }

    public function scopeBirthDateNextDays(Builder $builder, int $days)
    {
        return $this->applyDateScope($builder, 'birth_date', 'AtNextDays', $days);
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
            AllowedFilter::partial('name'),
            AllowedFilter::partial('email'),
            AllowedFilter::partial('nickname'),
            AllowedFilter::partial('username'),
            AllowedFilter::exact('is_active'),

            AllowedFilter::partial('phone_number'),

            AllowedFilter::callback('roles', function (Builder $query, $value) {
                if ($value === 'App User') {
                    // Filtra usuários que SÓ têm o papel 'App User'
                    $query->whereHas('roles', fn($q) => $q->where('name', 'App User'))
                        ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Premium', 'Affiliate', 'Admin', 'Owner']));
                } elseif ($value === 'Premium') {
                    // Filtra usuários que têm 'Premium', mas NÃO 'Affiliate'
                    $query->whereHas('roles', fn($q) => $q->where('name', 'Premium'))
                        ->whereDoesntHave('roles', fn($q) => $q->where('name', 'Affiliate'));
                } else {
                    // Filtro padrão para qualquer outro papel (ex: Affiliate, Admin)
                    $query->whereHas('roles', fn($q) => $q->where('name', $value));
                }
            }),


            AllowedFilter::scope('BirthDate'),
            AllowedFilter::scope('BirthDateBefore'),
            AllowedFilter::scope('BirthDateAfter'),
            AllowedFilter::scope('BirthDateBetween'),
            AllowedFilter::scope('BirthDateCurrentDay'),
            AllowedFilter::scope('BirthDateCurrentWeek'),
            AllowedFilter::scope('BirthDateCurrentMonth'),
            AllowedFilter::scope('BirthDateLastDays'),
            AllowedFilter::scope('BirthDateNextDays'),
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
     * subscription,activeMotives,activities
     */
    public static function ALLOWEDINCLUDES()
    {
        return ['activities', 'roles'];
    }

    /**
     * The allowed sorts attributes.
     * id,name,email,nickname,username,phone_number,birth_date,is_active,created_at,updated_at,deleted_at
     */
    public static function ALLOWEDSORTS()
    {
        return ['id', 'name', 'email', 'nickname', 'username', 'phone_number', 'birth_date', 'is_active', 'created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * The Default sorts attributes.
     * name,email,nickname,username,phone_number,birth_date
     */
    public static function DEFAULTSORT()
    {
        return ['-created_at', 'name', 'email', 'nickname', 'username', 'phone_number', 'birth_date'];
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
