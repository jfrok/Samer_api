<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderCreatedNotification extends Notification
{
    use Queueable;

    /** @var Order */
    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $frontend = config('app.frontend_url', 'http://localhost:3000');
        $trackingRef = $this->order->reference_number ?? $this->order->id;
        $trackingUrl = rtrim($frontend, '/') . '/order/' . $trackingRef;

        return (new MailMessage)
            ->subject('Your order has been placed')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
                ->line('Thank you for your order. Your order reference is: ' . ($this->order->reference_number ?? $this->order->order_number ?? $this->order->id))
            ->line('You can track your order status and details by clicking the button below:')
            ->action('Track Order', $trackingUrl)
            ->line('If you have any questions, reply to this email.');
    }
}
