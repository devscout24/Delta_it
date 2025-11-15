<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSystemNotification extends Notification
{
    use Queueable;

    private $title;
    private $description;
    private $time;

    public function __construct($title, $description = null, $time = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->time = $time ?? now();
    }


    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'time' => $this->time,
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
          return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
