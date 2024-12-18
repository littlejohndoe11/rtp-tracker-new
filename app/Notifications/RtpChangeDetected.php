<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RtpChangeDetected extends Notification
{
    use Queueable;

    protected $changes;

    public function __construct(array $changes)
    {
        $this->changes = $changes;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Significant RTP Changes Detected')
            ->line('The following significant RTP changes have been detected:');

        foreach ($this->changes as $change) {
            $game = $change['game'];
            $changeData = $change['change'];

            $message->line('')
                ->line("Game: {$game->name}")
                ->line("Provider: {$game->provider->name}")
                ->line("Old RTP: {$changeData->old_rtp}%")
                ->line("New RTP: {$changeData->new_rtp}%")
                ->line("Change: {$changeData->change_percentage}%");
        }

        return $message;
    }
}
