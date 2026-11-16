<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\DeleteUnverifiedUsers;
//use App\Console\Commands\SendScheduledNotifications;

class Kernel extends ConsoleKernel
{
    /**
     * Os comandos Artisan disponíveis na aplicação.
     *
     * @var array<class-string>
     */
    protected $commands = [
        // Registro explícito do comando de limpeza
        DeleteUnverifiedUsers::class,
        //        SendScheduledNotifications::class,
    ];

    /**
     * Define o agendamento de tarefas da aplicação.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Grava a data e hora atual no arquivo de log toda vez que o schedule é carregado
        file_put_contents(storage_path('logs/schedule_teste.log'), now() . PHP_EOL, FILE_APPEND);
        // Apaga contas não verificadas (há mais de 36 h) uma vez por hora
        $schedule->command('users:prune-unverified')
            ->hourly()            // executa a cada hora
            ->withoutOverlapping() // evita concorrência
            ->runInBackground();  // executa em segundo plano

        // $schedule->command('notifications:send-scheduled')
        //     ->dailyAt('14:00');
    }

    /**
     * Registra os comandos para a aplicação.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
