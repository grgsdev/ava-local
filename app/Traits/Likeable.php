<?php

namespace App\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Likeable
{
    /**
     * Define a relação polimórfica com os Likes.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * "Gostar" do modelo atual.
     */
    public function like()
    {
        // Se o usuário já gostou, não faz nada. Se não, cria o like.
        return $this->likes()->firstOrCreate([
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * "Não gostar" do modelo atual.
     */
    public function unlike()
    {
        // Apaga o like do usuário atual
        return $this->likes()->where('user_id', auth()->id())->delete();
    }

    /**
     * Alterna o estado de "gosto".
     */
    public function toggleLike()
    {
        if ($this->isLikedByCurrentUser()) {
            return $this->unlike();
        }
        return $this->like();
    }

    /**
     * Verifica se o modelo já foi "gostado" pelo usuário atual.
     */
    public function isLikedByCurrentUser(): bool
    {
        // A relação 'likes' aqui será pré-carregada para performance.
        return $this->likes->where('user_id', auth()->id())->isNotEmpty();
    }
}
