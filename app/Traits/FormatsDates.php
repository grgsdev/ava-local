<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait FormatsDates
{
    /**
     * Formata um objeto Carbon em um array padronizado com múltiplos formatos.
     *
     * @param \Illuminate\Support\Carbon|null $date
     * @return array|null
     */
    protected function formatDate(?Carbon $date): ?array
    {
        if (!$date) {
            return null;
        }
        Carbon::setLocale(app()->getLocale());

        return [
            'default'         => $date->toIso8601String(), // Formato padrão para APIs
            'short'           => $date->isoFormat('L'),          // Ex: 15/07/2025
            'short_with_time' => $date->isoFormat('L LT'), // Ex: 15/07/2025 11:51
            'human'           => $date->diffForHumans(),       // Ex: há 2 minutos
        ];
    }
}
