<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountUnlockedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.unlock_email_subject'))
            ->greeting(__('auth.unlock_email_greeting', ['name' => $notifiable->name]))
            ->line(__('auth.unlock_email_line_1'))
            ->line(__('auth.unlock_email_line_2'))
            ->action(__('auth.unlock_email_action'), route('login'))
            ->line(__('auth.unlock_email_line_3'));
    }
}
