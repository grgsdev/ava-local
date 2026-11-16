<?php

namespace App\Models\System;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\QueryBuilder\AllowedFilter;

class Role extends SpatieRole
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
