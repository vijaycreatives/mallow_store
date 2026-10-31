<?php

namespace App\Livewire;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Product> $lowStockProducts
 * @property-read string $subtotal
 * @property-read string $taxTotal
 * @property-read string $grandTotal
 * @property-read string $balance
 */
class NewOrder extends Component
{
    public string $email = '';

    public string $name = '';

    /** @var array<int, array{product_id: string|int, quantity: int|string}> */
    public array $items = [
        ['product_id' => '', 'quantity' => 1],
    ];

    public string $amountGiven = '';

    public ?array $createdOrder = null;

    public ?string $errorMessage = null;

    public function updatedEmail(): void
    {
        $this->errorMessage = null;
        $customer = Customer::where('email', $this->email)->first();
        if ($customer) {
            $this->name = $customer->name;
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::where('stock_quantity', '>', 0)->orderBy('name')->get();
    }

    #[Computed]
    public function lowStockProducts(): Collection
    {
        $threshold = config('store.low_stock_threshold', 10);

        return Product::where('stock_quantity', '<=', $threshold)->orderBy('stock_quantity')->get();
    }

    #[Computed]
    public function subtotal(): string
    {
        $total = '0';
        foreach ($this->items as $item) {
            if (! $item['product_id']) {
                continue;
            }
            $product = $this->products->firstWhere('id', (int) $item['product_id']);
            if ($product) {
                $qty = max(1, (int) $item['quantity']);
                $total = bcadd($total, bcmul((string) $product->price, (string) $qty, 2), 2);
            }
        }

        return $total;
    }

    #[Computed]
    public function taxTotal(): string
    {
        $total = '0';
        foreach ($this->items as $item) {
            if (! $item['product_id']) {
                continue;
            }
            $product = $this->products->firstWhere('id', (int) $item['product_id']);
            if ($product) {
                $qty = max(1, (int) $item['quantity']);
                $rowSub = bcmul((string) $product->price, (string) $qty, 2);
                $rowTax = bcmul($rowSub, bcdiv((string) $product->tax_percentage, '100', 6), 2);
                $total = bcadd($total, $rowTax, 2);
            }
        }

        return $total;
    }

    #[Computed]
    public function grandTotal(): string
    {
        return bcadd($this->subtotal, $this->taxTotal, 2);
    }

    #[Computed]
    public function balance(): string
    {
        if ($this->amountGiven === '' || (float) $this->amountGiven <= 0) {
            return '0.00';
        }

        return bcsub((string) $this->amountGiven, $this->grandTotal, 2);
    }

    public function generateBill(OrderService $orderService): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->errorMessage = null;
        $this->createdOrder = null;

        try {
            $order = $orderService->createOrder([
                'email' => $this->email,
                'name' => $this->name,
                'items' => array_map(fn ($i) => [
                    'product_id' => (int) $i['product_id'],
                    'quantity' => (int) $i['quantity'],
                ], $this->items),
            ]);

            $this->createdOrder = [
                'id' => $order->id,
                'grand_total' => $order->grand_total,
                'customer' => $order->customer->name,
            ];

            // Reset form
            $this->items = [['product_id' => '', 'quantity' => 1]];
            $this->amountGiven = '';
            unset($this->products, $this->lowStockProducts, $this->subtotal, $this->taxTotal, $this->grandTotal, $this->balance);
        } catch (InsufficientStockException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.new-order')
            ->layout('layouts.billing', ['title' => 'Store Billing — New Order']);
    }
}
