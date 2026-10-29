# 📋 Mallow Technologies — Laravel Take-Home: Master Plan

> **Project**: `mallow_store` · Laravel 13 + Livewire Starter Kit · PHP 8.3 · Pest

---

## 🗺️ High-Level Scope at a Glance

| Area | What to Build |
|---|---|
| **Schema** | `products`, `customers`, `orders`, `order_items` (+ pivot indexes) |
| **API** | 3 JSON endpoints (create order, order history, low-stock) |
| **UI** | Livewire billing screen matching the wireframe |
| **Queue** | `SendOrderConfirmationEmail` job dispatched on order creation |
| **Concurrency** | Pessimistic locking (`lockForUpdate`) on stock deduction |
| **Tests** | Pest feature + unit tests, including insufficient-stock edge case |
| **Docs** | `README.md` + `/prompts` folder |

---

## 🏗️ Phase 1 — Database Schema

### Tables

```
products
  id, name, code (unique), price (decimal 10,2), tax_percentage (decimal 5,2), stock_quantity (int), timestamps

customers
  id, name, email (unique), timestamps

orders
  id, customer_id (FK), subtotal (decimal 10,2), tax_total (decimal 10,2), grand_total (decimal 10,2), timestamps

order_items
  id, order_id (FK), product_id (FK), quantity (int), unit_price (decimal 10,2), tax_percentage (decimal 5,2), line_subtotal, line_tax, line_total, timestamps
```

> **Design decision**: Store `unit_price` and `tax_percentage` snapshot on `order_items` — prices can change after the order is placed.
> **Supporting table**: `jobs` (already present in starter), `failed_jobs` for the queue.

### Migration Order
1. `create_products_table`
2. `create_customers_table`
3. `create_orders_table` (needs `customers`)
4. `create_order_items_table` (needs `orders` + `products`)

---

## 🔗 Phase 2 — Eloquent Models & Relationships

```
Customer  hasMany  Order
Order     belongsTo Customer
Order     hasMany  OrderItem
OrderItem belongsTo Order
OrderItem belongsTo Product
Product   hasMany  OrderItem
```

**Casts to add on `Product`**: `price` → `decimal:2`, `tax_percentage` → `decimal:2`
**Casts on `Order`**: `subtotal`, `tax_total`, `grand_total` → `decimal:2`

---

## 🛣️ Phase 3 — API Endpoints

### 3a. POST `/api/orders` — Create Order

**Request body:**
```json
{
  "email": "thomas@example.com",
  "name": "Thomas",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 5 }
  ]
}
```

**Validation Rules (FormRequest):**
- `email` → required, email format
- `name` → required_without existing customer by email
- `items` → required, array, min:1
- `items.*.product_id` → exists:products,id
- `items.*.quantity` → integer, min:1

**Service: `OrderService::createOrder()`**
1. `DB::transaction()` wrapping everything
2. Find or create `Customer` by email
3. For each item: `Product::lockForUpdate()->find(id)` → check stock → throw if insufficient
4. Compute: `line_subtotal = price × qty`, `line_tax = line_subtotal × (tax_pct/100)`, `line_total = line_subtotal + line_tax`
5. Sum all lines → `order subtotal`, `order tax_total`, `order grand_total`
6. Create `Order` + `OrderItem` records
7. Deduct stock: `$product->decrement('stock_quantity', $qty)`
8. After commit → `dispatch(new SendOrderConfirmationEmail($order))`
9. Return `OrderResource` with `201`

**Response (201):**
```json
{
  "data": {
    "id": 12,
    "customer": { "name": "Thomas", "email": "thomas@example.com" },
    "items": [...],
    "subtotal": "210.00",
    "tax_total": "17.20",
    "grand_total": "227.20",
    "created_at": "..."
  }
}
```

---

### 3b. GET `/api/customers/{email}/orders` — Order History

- Lookup customer by email, 404 if not found
- Eager-load `orders.orderItems.product`
- Return paginated `OrderCollection`

---

### 3c. GET `/api/products/low-stock` — Low Stock Alert

- Read threshold from config: `config('store.low_stock_threshold', 10)`
- Add to `config/store.php` + `.env` key `LOW_STOCK_THRESHOLD=10`
- Query: `Product::where('stock_quantity', '<=', $threshold)->get()`
- Return `ProductResource` collection

---

## ⚙️ Phase 4 — Concurrency Safety

**Strategy: Pessimistic Locking**

```php
// Inside DB::transaction()
$product = Product::lockForUpdate()->findOrFail($item['product_id']);

if ($product->stock_quantity < $item['quantity']) {
    throw new InsufficientStockException($product);
}

$product->decrement('stock_quantity', $item['quantity']);
```

> `lockForUpdate()` acquires a row-level exclusive lock in MySQL/PostgreSQL. If two requests arrive simultaneously, the second waits until the first transaction commits — then sees the updated stock and fails cleanly. This is exactly what the spec requires.

---

## 📬 Phase 5 — Queued Job

**Job: `app/Jobs/SendOrderConfirmationEmail.php`**

```php
class SendOrderConfirmationEmail implements ShouldQueue
{
    public function __construct(public Order $order) {}

    public function handle(): void
    {
        Log::info("Order confirmation sent", [
            'order_id' => $this->order->id,
            'customer_email' => $this->order->customer->email,
            'grand_total' => $this->order->grand_total,
        ]);
    }
}
```

- Use `QUEUE_CONNECTION=database` (jobs table already exists)
- Dispatch after transaction commits: `SendOrderConfirmationEmail::dispatch($order)->afterCommit()`
- `afterCommit()` ensures the job only fires if the DB transaction succeeded

---

## 🌱 Phase 6 — Factories & Seeders

**ProductFactory**: 20 products with realistic names, random codes (uppercase 6-char), prices between 10–500, tax 0/5/12/18 (Indian GST slabs), stock 0–100.

**CustomerFactory**: 10 customers with Faker names + emails.

**DatabaseSeeder**:
```php
Product::factory(20)->create();
Customer::factory(10)->create();
```

---

## 🖥️ Phase 7 — UI (Livewire Billing Screen)

Match the wireframe:
- Email input → on blur, auto-fill Name if customer exists (Livewire `wire:model.lazy`)
- Dynamic product rows (add/remove) with dropdown + qty
- Live computed: subtotal, tax, grand total
- Low Stock Alert sidebar panel (call low-stock API or load via Livewire)
- Generate Bill button → calls `OrderService` directly or hits the API
- Show balance-to-return calculator (Amount Given - Grand Total)

**Livewire Component**: `app/Livewire/NewOrder.php`

---

## 🧪 Phase 8 — Tests (Pest)

### Feature Tests

| Test | Scenario |
|---|---|
| `it_creates_an_order_successfully` | Happy path — stock deducted, totals correct, 201 returned |
| `it_fails_when_stock_is_insufficient` | Quantity > stock → 422 with error message |
| `it_finds_or_creates_customer_by_email` | Existing email reuses customer, new email creates one |
| `it_dispatches_order_confirmation_job` | `Queue::fake()` → assert job dispatched |
| `it_returns_order_history_for_customer` | Correct orders returned, 404 for unknown email |
| `it_returns_low_stock_products` | Only products below threshold returned |
| `it_handles_concurrent_stock_requests` | Run 2 orders for last unit → exactly 1 succeeds (integration) |
| `it_rejects_invalid_product_id` | Non-existent product → validation error |

### Unit Tests

| Test | Target |
|---|---|
| `it_computes_line_totals_correctly` | `OrderService` math with tax |
| `it_computes_order_grand_total` | Sum of line totals |

---

## 📁 File Structure Plan

```
app/
├── Actions/              ← thin wrappers if needed
├── Exceptions/
│   └── InsufficientStockException.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── OrderController.php
│   │   ├── CustomerOrderController.php
│   │   └── ProductController.php
│   ├── Requests/
│   │   └── CreateOrderRequest.php
│   └── Resources/
│       ├── OrderResource.php
│       ├── OrderCollection.php
│       ├── OrderItemResource.php
│       └── ProductResource.php
├── Jobs/
│   └── SendOrderConfirmationEmail.php
├── Livewire/
│   └── NewOrder.php
├── Models/
│   ├── Customer.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── Product.php
└── Services/
    └── OrderService.php

config/
└── store.php   ← low_stock_threshold

database/
├── factories/
│   ├── CustomerFactory.php
│   └── ProductFactory.php
├── migrations/
│   ├── ..._create_products_table.php
│   ├── ..._create_customers_table.php
│   ├── ..._create_orders_table.php
│   └── ..._create_order_items_table.php
└── seeders/
    └── DatabaseSeeder.php

routes/
├── api.php     ← API routes
└── web.php     ← Livewire UI route

tests/
├── Feature/
│   ├── Api/OrderCreationTest.php
│   ├── Api/CustomerOrderHistoryTest.php
│   └── Api/LowStockTest.php
└── Unit/
    └── OrderServiceTest.php

prompts/        ← AI prompt screenshots
README.md
```

---

## 📝 README Outline

```markdown
# Mallow Store

## Setup
1. git clone ...
2. composer install
3. cp .env.example .env && php artisan key:generate
4. Configure DB in .env
5. php artisan migrate --seed
6. php artisan queue:work (separate terminal)
7. npm install && npm run dev
8. php artisan serve

## Running Tests
php artisan test

## Assumptions & Design Decisions
- Prices snapshot at order time (order_items stores unit_price)
- Pessimistic locking chosen over optimistic for simplicity
- Queue driver: database (no Redis dependency)
- LOW_STOCK_THRESHOLD configurable via .env
- Customer created automatically on first order if email is new
- Tax is per-product (supports mixed GST rates in one order)
```

---

## ⏱️ Suggested Time Budget (3 days)

| Day | Tasks |
|---|---|
| **Day 1** | Schema + migrations, Models + relationships, Factories + Seeders, API routes skeleton |
| **Day 2** | `OrderService` (business logic + locking), all 3 API controllers + FormRequest, Queued Job, API Resources |
| **Day 3** | Livewire UI, Pest tests, README, prompt log, screen recording |

---

## 🚦 Pre-Submission Checklist

- [ ] `php artisan migrate:fresh --seed` runs without errors
- [ ] `php artisan test` — all tests green
- [ ] All 3 API endpoints return correct JSON
- [ ] Insufficient stock returns 422 with descriptive message
- [ ] Queue job appears in `storage/logs/laravel.log`
- [ ] Low-stock threshold is configurable via `.env`
- [ ] `README.md` documents all assumptions
- [ ] `/prompts` folder has AI prompt screenshots
- [ ] Screen recording is narrated and ≥ 5 min
- [ ] GitHub repo shared with the recruiter
