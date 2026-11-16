<?php

namespace App\Notifications;

use App\Models\Company; // <-- 1. IMPORTE O MODELO COMPANY
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    // 2. CRIE UMA PROPRIEDADE PARA GUARDAR A EMPRESA
    public Company $company;

    /**
     * Crie uma nova instância da notificação.
     */
    // 3. ATUALIZE O CONSTRUTOR PARA RECEBER A EMPRESA
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'registration.complete',
            now()->addHours(1),
            ['user' => $notifiable->id]
        );

        // 4. USE O NOME DA EMPRESA NA MENSAGEM DO E-MAIL
        return (new MailMessage)
            ->subject("Convite para a equipe da {$this->company->name}!")
            ->line("Você foi convidado para se juntar à equipe da '{$this->company->name}' na nossa plataforma.")
            ->line('Para começar, você precisa de definir uma senha para a sua conta.')
            ->action('Definir Senha', $url)
            ->line('Este link de convite irá expirar em 60 minutos.')
            ->line('Se não estava à espera deste convite, pode ignorar este e-mail.');
    }
}
