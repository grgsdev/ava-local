<?php

namespace App\Support\QueryBuilder\Sorts;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class CauserNameSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property)
    {
        $direction = $descending ? 'DESC' : 'ASC';

        // Este código junta a tabela 'users' à nossa consulta de 'activities'
        // e permite ordenar pelo campo 'name' da tabela de usuários.
        $query
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->orderBy('users.name', $direction)
            // É importante selecionar as colunas de 'activity_log' para evitar conflitos de 'id'
            ->select('activity_log.*');
    }
}
