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
            ->subject('تم استلام طلبك بنجاح')
            ->greeting('مرحباً ' . ($notifiable->name ?? ''))
            ->line('شكراً لتسوقك معنا. رقم مرجع طلبك: ' . ($this->order->reference_number ?? $this->order->order_number ?? $this->order->id))
            ->line('يمكنك متابعة حالة الطلب والتفاصيل عبر الرابط التالي:')
            ->action('تتبع الطلب', $trackingUrl)
            ->line('لأي استفسار، يرجى الرد على هذه الرسالة.');
    }
}
