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
            ->subject('New Order Created: ' . $order->order_number)
            ->greeting('Hello Store Owner,')
            ->line('A new order has been placed.')
            ->line('Order Number: ' . $order->order_number)
            ->line('Reference: ' . $order->reference_number)
            ->line('Customer: ' . ($order->user?->name ?? ($order->shippingAddress?->first_name . ' ' . $order->shippingAddress?->last_name)))
            ->line('Total Amount: ' . number_format((float) $order->total_amount, 2) . ' IQD')
            ->line('Payment Method: ' . ucfirst($order->payment_method))
            ->line('Status: ' . ucfirst($order->status))
            ->line('Items:')
            ->line($order->items->map(function($item) {
                $productTitle = $item->productVariant?->product?->title ?? 'Product';
                return '- ' . $productTitle . ' x' . $item->quantity . ' @ ' . number_format($item->price, 2) . ' IQD';
            })->implode("\n"))
            ->action('View Order', url('/admin/orders/' . $order->id))
            ->line('Thank you.');
    }
}
