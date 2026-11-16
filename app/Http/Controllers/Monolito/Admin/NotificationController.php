<?php

namespace App\Http\Controllers\Monolito\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markAsRead(DatabaseNotification $notification)
    {
        // Garante que o usuário só pode marcar suas próprias notificações como lidas
        if (Auth::User()->id === $notification->notifiable_id) {
            $notification->markAsRead();
        }
        return back();
    }
    public function read(Request $request, $notificationId)
    {
        $notification = $request->user()->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }
        // Redireciona para o link da notificação, se existir
        if ($notification->data['link'] ?? false) {
            return redirect($notification->data['link']);
        } else {
            return back();
        }
    }

    public function destroy(DatabaseNotification $notification)
    {
        if (Auth::User()->id === $notification->notifiable_id) {
            $notification->delete();
        }
        return back()->with('success', 'Notificação removida.');
    }

    // NOVO MÉTODO PARA MARCAR TODAS COMO LIDAS
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }
}
