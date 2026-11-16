<?php

namespace App\Traits;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Commentable
{
    /**
     * Define a relação polimórfica com os Comentários.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Adiciona um comentário a este modelo.
     */
    public function addComment(string $body, ?int $userId = null)
    {
        $comment = new Comment([
            'body' => $body,
            'user_id' => $userId ?? auth()->id(),
        ]);

        $this->comments()->save($comment);

        return $comment;
    }
}
