<?php

namespace App\Notifications;

use App\Models\ScheduledNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralUserNotification extends Notification  implements ShouldQueue
{
    use Queueable;

    public function __construct(public ScheduledNotification $scheduledNotification) {}

    public function via(object $notifiable): array
    {
        // Retorna dinamicamente os canais que o Admin selecionou
        return $this->scheduledNotification->delivery_channels;
    }

    // public function toMail(object $notifiable): MailMessage
    // {

    //     return (new MailMessage)
    //         ->line($this->scheduledNotification->title)
    //         ->line($this->scheduledNotification->body);
    //     //->action('Ver no App', url('/'));
    // }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->scheduledNotification->title) // define o assunto do email
            ->view('emails.general-user-notification', [
                'name' => $notifiable->name ?? 'Usuário', // pode ajustar com o nome real do destinatário
                'title' => $this->scheduledNotification->title,
                'body' => $this->scheduledNotification->body,
            ]);
    }
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->scheduledNotification->title,
            'body' => $this->scheduledNotification->body,
        ];
    }
}
