<?php

namespace App\Notifications;

use App\Mail\InfoMailWithQrCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Log;

class InfoNotification extends Notification
{
    use Queueable;

    private $requestData;

    /**
     * Create a new notification instance.
     */
    public function __construct($requestData)
    {
        $this->requestData = $requestData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->requestData['message'],
            'url' => $this->requestData['url'],
            'message_type' => $this->requestData['message_type'],
            'thumbnail' => $this->requestData['thumbnail'],
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable)
    {
        Log::info(' broadcast type' . $this->requestData['message_type']);
        return new BroadcastMessage([
            'title' => $this->requestData['title'],
            'message' => $this->requestData['message'],
            'url' => $this->requestData['url'],
            'message_type' => $this->requestData['message_type'],
            'thumbnail' => $this->requestData['thumbnail'],
        ]);
    }
}
