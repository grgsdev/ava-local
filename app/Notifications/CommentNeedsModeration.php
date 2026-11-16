<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentNeedsModeration extends Notification
{
    use Queueable;

    public Comment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Vamos enviar a notificação apenas para o banco de dados por enquanto.
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Estes são os dados que serão salvos como JSON no banco de dados.
        // Eles serão usados para renderizar a notificação no painel.
        return [
            'message' => 'Um novo comentário de "' . $this->comment->user->name . '" precisa de moderação.',
            'link' => route('admin.moderation.index'), // O link levará para o painel de moderação
            'comment_id' => $this->comment->id,
            'author_name' => $this->comment->user->name,
        ];
    }
}
