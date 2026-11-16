<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Comment;
use App\Models\CensoredWord;
use App\Models\ModerationCase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CommentNeedsModeration;

class CommentObserver
{
    /**
     * Handle the Comment "created" event.
     */
    public function created(Comment $comment): void
    {
        // 1. Busca todas as palavras censuradas
        $censoredWords = CensoredWord::pluck('word')->toArray();

        if (empty($censoredWords)) {
            return;
        }

        // 2. Verifica se alguma palavra censurada existe no corpo do comentário
        $body = $comment->body;
        $found = false;
        foreach ($censoredWords as $word) {
            // str_contains é case-sensitive, stripos não. Usamos stripos para ser case-insensitive.
            if (stripos($body, $word) !== false) {
                $found = true;
                break; // Encontrou uma, não precisa continuar
            }
        }

        // 3. Se encontrou uma palavra...
        if ($found) {
            // Cria o caso de moderação
            ModerationCase::create([
                'comment_id' => $comment->id,
            ]);

            // Encontra os moderadores e envia a notificação
            $moderators = User::role('moderator')->get();
            if ($moderators->isNotEmpty()) {
                Notification::send($moderators, new CommentNeedsModeration($comment));
            }
            $admins = User::role('admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new CommentNeedsModeration($comment));
            }
        }
    }

    /**
     * Handle the Comment "updated" event.
     */
    public function updated(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "restored" event.
     */
    public function restored(Comment $comment): void
    {
        //
    }

    /**
     * Handle the Comment "force deleted" event.
     */
    public function forceDeleted(Comment $comment): void
    {
        //
    }
}
