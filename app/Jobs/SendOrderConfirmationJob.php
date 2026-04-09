<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationJob implements ShouldQueue
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
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load the order with necessary relationships
            $this->order->load(['user', 'items.productVariant.product', 'shippingAddress']);

            // Send notification to the customer
            if ($this->order->user) {
                $this->order->user->notify(new OrderCreatedNotification($this->order));
                Log::info('Order confirmation email sent successfully', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'user_id' => $this->order->user->id
                ]);
            } else {
                // For guest orders, we could send email directly to shipping address email
                Log::info('Guest order created - no user notification sent', [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $this->order->id,
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
        Log::critical('Order confirmation email job failed after all retries', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception->getMessage()
        ]);
    }
}
