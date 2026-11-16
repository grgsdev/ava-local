<?php

namespace App\Notifications;

use App\Models\Answer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAnswerToYourQuestion extends Notification
{
    use Queueable;

    public Answer $answer;

    public function __construct(Answer $answer)
    {
        $this->answer = $answer;
    }

    public function via(object $notifiable): array
    {
        // Envia tanto para o painel quanto por e-mail
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $question = $this->answer->question;
        $course = $question->questionable->course;
        $url = route('public.courses.show', ['course' => $course->slug, 'question' => $question->id]);

        return (new MailMessage)
            ->subject('Sua pergunta foi respondida!')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Um instrutor respondeu à sua pergunta "' . $question->title . '" no projeto "' . $course->title . '".')
            ->action('Ver a Resposta', $url)
            ->line('Obrigado por fazer parte da nossa comunidade!');
    }

    public function toArray(object $notifiable): array
    {
        $question = $this->answer->question;
        $course = $question->questionable->course;

        return [
            'message' => 'Sua pergunta "' . $question->title . '" foi respondida!',
            'link' => route('public.courses.show', ['course' => $course->slug, 'question' => $question->id]),
        ];
    }
}
