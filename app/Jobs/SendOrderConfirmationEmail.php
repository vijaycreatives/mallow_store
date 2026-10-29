<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    /**
     * Simulate sending an order confirmation email.
     * In production this would use a Mailable; here we log the confirmation.
     */
    public function handle(): void
    {
        $order = $this->order->load('customer', 'orderItems.product');

        Log::info('Order confirmation email sent', [
            'order_id' => $order->id,
            'customer_name' => $order->customer->name,
            'customer_email' => $order->customer->email,
            'grand_total' => $order->grand_total,
            'items_count' => $order->orderItems->count(),
        ]);
    }
}
