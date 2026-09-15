<?php

namespace App\Notifications;

use App\Models\Keyword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KeywordDropped extends Notification
{
    use Queueable;

    public function __construct(public Keyword $keyword, public int $drop)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Keyword ranking dropped')
            ->line("\"{$this->keyword->keyword}\" dropped {$this->drop} positions.")
            ->action('View keyword', route('keywords.show', [$this->keyword->project, $this->keyword]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'keyword_id' => $this->keyword->id,
            'drop' => $this->drop,
        ];
    }
}
