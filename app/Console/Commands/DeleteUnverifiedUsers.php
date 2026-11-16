<?php

// app/Console/Commands/DeleteUnverifiedUsers.php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use App\Traits\LogsScheduledCommands;

class DeleteUnverifiedUsers extends Command
{
    use LogsScheduledCommands;
    protected $signature   = 'users:prune-unverified';
    protected $description = 'Apaga contas não verificadas após 36 horas';

    public function handle()
    {
        $total = User::whereNull('email_verified_at')
            ->where('verification_sent_at', '<', now()->subHours(36))
            ->delete();

        $this->info("{$total} conta(s) removida(s).");
    }
}
