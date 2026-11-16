<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ScheduledNotification;
use App\Traits\LogsScheduledCommands;
use Illuminate\Support\Facades\Notification;
use App\Notifications\GeneralUserNotification;

class SendScheduledNotifications extends Command
{
    use LogsScheduledCommands;
    protected $signature = 'notifications:send-scheduled';
    protected $description = 'Envia as notificações agendadas ativas para os usuários alvo.';

    public function handle()
    {

        try {
            $today = Carbon::today();
            $notifications = ScheduledNotification::where('is_active', true)
                ->where('start_date', '<=', $today)
                ->get();
            $filteredNotifications = $notifications->filter(function ($notification) use ($today) {
                $endDate = Carbon::parse($notification->start_date)->addDays($notification->period_in_days);
                return $today->lte($endDate);
            });
            foreach ($filteredNotifications as $notification) {
                $this->info("A processar notificação: {$notification->title}");
                $targetUsers = User::role($notification->target_roles)
                    ->whereDoesntHave('roles', function ($query) {
                        $query->where('name', 'Admin');
                    })
                    ->get();
                if ($targetUsers->isNotEmpty()) {
                    Notification::send($targetUsers, new GeneralUserNotification($notification));
                    $this->logCommandExecution("Notificação agendada {$notification->title} enviada com sucesso para {$targetUsers->count()} usuários.", true);
                    $this->info("Notificação agendada {$notification->title} enviada com sucesso para {$targetUsers->count()} usuários.");
                }
            }
        } catch (\Exception $e) {
            // Registro de falha no log
            $this->logCommandExecution($e->getMessage(), false);
            $this->error("Falha ao enviar notificações agendadads: {$e->getMessage()}");
        }

        return 0;
    }
}
