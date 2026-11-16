<?php

namespace App\Models\System;

use Spatie\QueryBuilder\AllowedFilter;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use Cachable;
    public static function ALLOWEDFILTERS()
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::scope('Search'),
        ];
    }

    public static function ALLOWEDSORTS()
    {
        return ['id', 'name', 'created_at'];
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
