<?php

namespace App\Traits;

use App\Models\User;
use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\CheckIn; // Criaremos este modelo em breve

trait Checkinable
{
    /**
     * Define a relação polimórfica para os check-ins.
     */
    public function checkIns(): MorphMany
    {
        return $this->morphMany(CheckIn::class, 'checkable');
    }

    /**
     * Adiciona um check-in para um usuário, respeitando a regra de 1 hora.
     * Retorna true se o check-in foi bem-sucedido, false caso contrário.
     */
    public function addCheckIn(User $user): bool
    {
        $settings = PlatformSetting::first();
        $cooldownMinutes = $settings?->checkin_cooldown_minutes;
        if (!$cooldownMinutes) {
            $cooldownMinutes = config('app.checkin_cooldown_minutes', 60); // Valor padrão
        }
        $lastCheckIn = $this->checkIns()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        if ($lastCheckIn && $lastCheckIn->created_at->gt(now()->subMinutes($cooldownMinutes))) {
            // Ainda não passou o tempo, não pode fazer check-in
            return false;
        }



        // Se houver um check-in anterior, verifica se já passou 1 hora
        // if ($lastCheckIn && $lastCheckIn->created_at->gt(now()->subHour())) {
        //     // Ainda não passou 1 hora, não pode fazer check-in
        //     return false;
        // }

        $this->checkIns()->create(['user_id' => $user->id]);
        return true;
    }

    /**
     * Verifica se o modelo teve um check-in pelo usuário atual na última hora.
     */
    public function getIsCheckedInByMeAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $cooldownMinutes = config('app.checkin_cooldown_minutes', 60);
        $user = auth('sanctum')->user();
        // Usamos a relação 'latestCheckInByCurrentUser' que carregamos no controller
        return $this->checkIns()
            ->where('user_id', $user->id)
            ->where('created_at', '>', now()->subMinutes($cooldownMinutes))
            ->exists();
    }

    /**
     * NOVO ACESSÓRIO: Verifica se o usuário autenticado já fez check-in neste
     * local em qualquer momento no passado.
     */
    public function getHasEverCheckedInByMeAttribute(): bool
    {
        if (!auth('sanctum')->check()) {
            return false;
        }

        // A propriedade 'check_ins_by_current_user_count' será carregada no controller.
        // Se a contagem for maior que 0, significa que ele já fez check-in.
        return ($this->check_ins_by_current_user_count ?? 0) > 0;
    }
}
