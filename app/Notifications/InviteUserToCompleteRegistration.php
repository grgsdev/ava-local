<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class InviteUserToCompleteRegistration extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Cria uma URL assinada que expira em 48 horas
        $url = URL::temporarySignedRoute(
            'registration.complete',
            now()->addHours(48),
            ['user' => $notifiable->id]
        );

        return (new MailMessage)
            ->subject('Complete seu Cadastro na Plataforma')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Você foi convidado para se juntar à nossa plataforma. Para finalizar seu cadastro, por favor, clique no botão abaixo.')
            ->action('Finalizar Cadastro', $url)
            ->line('Este link de convite irá expirar em 48 horas.')
            ->line('Se você não esperava este convite, nenhuma ação é necessária.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
