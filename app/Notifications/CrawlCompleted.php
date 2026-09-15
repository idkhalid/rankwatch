<?php

namespace App\Notifications;

use App\Models\Crawl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrawlCompleted extends Notification
{
    use Queueable;

    public function __construct(public Crawl $crawl)
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
            ->subject('RankWatch crawl completed')
            ->line("RankWatch crawled {$this->crawl->project->name}.")
            ->line("Pages crawled: {$this->crawl->pages_crawled}")
            ->line("Issues found: {$this->crawl->issues_found}")
            ->action('View project', route('projects.show', $this->crawl->project));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'crawl_id' => $this->crawl->id,
        ];
    }
}
