<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Favoriteable
{
    /**
     * Define a relação polimórfica para os favoritos.
     */
    public function favorites(): MorphToMany
    {
        return $this->morphToMany(User::class, 'favoriteable', 'favorites');
    }

    /**
     * Adiciona ou remove um item dos favoritos de um usuário.
     */
    public function toggleFavorite(User $user): void
    {
        $this->favorites()->toggle($user);
    }

    /**
     * Verifica se o modelo foi favoritado pelo usuário atual.
     */
    public function getIsFavoritedByMeAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Carrega a informação se ainda não estiver carregada para evitar queries extras
        if (! $this->relationLoaded('favorites')) {
            $this->load('favorites');
        }

        return $this->favorites->contains(auth()->user());
    }
}
