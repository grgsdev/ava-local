<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeletionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Construímos o link de confirmação com o token e o e-mail
        $url = route('account-deletion.confirm', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Confirmação de Exclusão de Conta Clube Lupa')
            ->line('Você está a recebendo este e-mail porque recebemos um pedido de exclusão para a sua conta.')
            ->line('Este link de exclusão irá expirar em 60 minutos.')
            ->action('Confirmar Exclusão da Conta', $url)
            ->line('Se você não solicitou a exclusão da sua conta, nenhuma ação é necessária.');
    }
}
