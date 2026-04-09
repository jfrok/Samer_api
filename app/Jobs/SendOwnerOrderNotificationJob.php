<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OwnerOrderCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendOwnerOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * The order instance.
     *
     * @var Order
     */
    public $order;

    /**
     * The owner email address.
     *
     * @var string
     */
    public $ownerEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, string $ownerEmail)
    {
        $this->order = $order;
        $this->ownerEmail = $ownerEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load the order with necessary relationships
            $this->order->load(['user', 'items.productVariant.product', 'shippingAddress']);

            // Send notification to the owner
            Notification::route('mail', $this->ownerEmail)
                ->notify(new OwnerOrderCreatedNotification($this->order));

            Log::info('Owner order notification sent successfully', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'owner_email' => $this->ownerEmail
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send owner order notification', [
                'order_id' => $this->order->id,
                'owner_email' => $this->ownerEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('Owner order notification job failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'owner_email' => $this->ownerEmail,
            'error' => $exception->getMessage()
        ]);
    }
}
