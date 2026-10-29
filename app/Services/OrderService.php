<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationEmail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order with concurrency-safe stock deduction.
     *
     * Strategy: pessimistic locking (SELECT ... FOR UPDATE) inside a DB
     * transaction. If two simultaneous requests target the same product row,
     * the second waits for the first transaction to commit before it proceeds,
     * so it reads the updated stock and fails cleanly — no overselling.
     *
     * @param  array{email: string, name?: string, items: array<array{product_id: int, quantity: int}>}  $data
     *
     * @throws InsufficientStockException
     */
    public function createOrder(array $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            // 1. Find or create the customer by email
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'] ?? $data['email']],
            );

            // 2. Lock + validate stock for all items before touching anything
            $resolvedItems = [];

            foreach ($data['items'] as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw new InsufficientStockException($product, $item['quantity']);
                }

                $resolvedItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                ];
            }

            // 3. Compute totals
            $subtotal = '0';
            $taxTotal = '0';

            $orderItemData = [];

            foreach ($resolvedItems as $resolved) {
                $product = $resolved['product'];
                $quantity = $resolved['quantity'];

                $rowSubtotal = bcmul((string) $product->price, (string) $quantity, 2);
                $rowTax = bcmul($rowSubtotal, bcdiv((string) $product->tax_percentage, '100', 6), 2);
                $rowTotal = bcadd($rowSubtotal, $rowTax, 2);

                $subtotal = bcadd($subtotal, $rowSubtotal, 2);
                $taxTotal = bcadd($taxTotal, $rowTax, 2);

                $orderItemData[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'tax_percentage' => $product->tax_percentage,
                    'row_subtotal' => $rowSubtotal,
                    'row_tax' => $rowTax,
                    'row_total' => $rowTotal,
                ];
            }

            $grandTotal = bcadd($subtotal, $taxTotal, 2);

            // 4. Persist order + items
            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            $order->orderItems()->createMany($orderItemData);

            // 5. Deduct stock for each product
            foreach ($resolvedItems as $resolved) {
                $resolved['product']->decrement('stock_quantity', $resolved['quantity']);
            }

            return $order;
        });

        // 6. Dispatch the confirmation job AFTER the transaction commits
        //    to ensure the job only fires when the DB write is durable.
        SendOrderConfirmationEmail::dispatch($order)->afterCommit();

        return $order->load('customer', 'orderItems.product');
    }
}
