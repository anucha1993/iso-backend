<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Generic in-app (database) notification for the ISMS workflow
 * (submit / approve / reject events). Rendered by the header bell + MyJob.
 */
class WorkflowNotification extends Notification
{
    /**
     * @param  string  $type     Event key: submitted | approved | rejected | info
     * @param  string  $title    Short headline (Thai)
     * @param  string  $message  Body line (Thai)
     * @param  string|null  $url  In-app link to open (e.g. /records/5?month=3)
     */
    public function __construct(
        public string $type,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
