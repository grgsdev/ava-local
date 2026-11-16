<?php

namespace App\Notifications;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewQuestionForAuthor extends Notification
{
    use Queueable;

    public Question $question;

    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    public function via(object $notifiable): array
    {
        // Apenas notificação interna no painel
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $course = $this->question->questionable->course; // Pega o curso através do vídeo

        return [
            'message' => 'Nova pergunta de "' . $this->question->user->name . '" no seu curso.',
            'link' => route('public.courses.show', ['course' => $course->slug, 'question' => $this->question->id]),
            'question_title' => $this->question->title,
        ];
    }
}
