<?php

namespace App\Models;

use App\Traits\Likeable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory, Likeable;

    protected $fillable = [
        'user_id',
        'body',
        'parent_id'
    ];

    /**
     * Pega o modelo pai que pode ser comentado (Post, Page, etc.).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Um comentário pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Define a relação para buscar todas as "respostas" (filhos).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->with([

            'likes' => fn($q) => $q->where('user_id', auth()->id()),
        ])->withCount('likes');
    }
    public function allReplies(): HasMany
    {
        return $this->replies()->with([
            'user',
            'allReplies',
            'likes' => fn($q) => $q->where('user_id', auth()->id()),
        ])->withCount('likes');
    }
}
