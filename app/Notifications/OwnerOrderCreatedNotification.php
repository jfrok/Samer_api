<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Order;

class OwnerOrderCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $order = $this->order->load(['user', 'items.productVariant.product', 'shippingAddress']);

        return (new MailMessage)
            ->subject('تم إنشاء طلب جديد: ' . $order->order_number)
            ->greeting('مرحباً بصاحب المتجر،')
            ->line('تم تقديم طلب جديد.')
            ->line('رقم الطلب: ' . $order->order_number)
            ->line('رقم المرجع: ' . $order->reference_number)
            ->line('العميل: ' . ($order->user?->name ?? ($order->shippingAddress?->first_name . ' ' . $order->shippingAddress?->last_name)))
            ->line('إجمالي المبلغ: ' . number_format((float) $order->total_amount, 2) . ' د.ع')
            ->line('طريقة الدفع: ' . ucfirst($order->payment_method))
            ->line('الحالة: ' . ucfirst($order->status))
            ->line('العناصر:')
            ->line($order->items->map(function($item) {
                $productTitle = $item->productVariant?->product?->title ?? 'المنتج';
                return '- ' . $productTitle . ' ×' . $item->quantity . ' بسعر ' . number_format($item->price, 2) . ' د.ع';
            })->implode("\n"))
            ->action('عرض الطلب', url('/admin/orders/' . $order->id))
            ->line('شكراً لكم.');
    }
}
