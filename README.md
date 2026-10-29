# Mallow Store

> **Mallow Technologies — Laravel Developer Mini Task**  
> A retail counter application for recording customer orders and keeping stock in sync.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 (PHP 8.3) |
| Frontend | Livewire 4 + Flux UI |
| Database | SQLite (dev) / MySQL (prod) |
| Queue | Database driver |
| Testing | Pest 5 |
| Asset Pipeline | Vite + Tailwind CSS v4 |

---

## Setup & Running

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+ / npm

### 1. Clone and install

```bash
git clone <your-repo-url>
cd mallow_store
composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`. For SQLite (default):

```env
DB_CONNECTION=sqlite
# DB_DATABASE is auto-resolved to database/database.sqlite
```

Key custom environment variables:

```env
QUEUE_CONNECTION=database    # use database queue
LOW_STOCK_THRESHOLD=10       # products at/below this qty appear in the low-stock API
```

### 3. Database

```bash
php artisan migrate --seed
# Seeds 20 products (with realistic Indian retail names + GST tax slabs)
# and 10 sample customers
```

### 4. Start the application

Open **three** terminal tabs:

```bash
# Tab 1 — PHP dev server
php artisan serve

# Tab 2 — Queue worker (processes the confirmation email job)
php artisan queue:work

# Tab 3 — Vite dev server (hot reload)
npm run dev
```

Visit **http://localhost:8000/billing** for the UI.

---

## API Endpoints

All endpoints are prefixed with `/api`.

### POST `/api/orders` — Create Order

```http
POST /api/orders
Content-Type: application/json

{
  "email": "thomas@example.com",
  "name": "Thomas",
  "items": [
    { "product_id": 1, "quantity": 2 },
    { "product_id": 3, "quantity": 5 }
  ]
}
```

- Finds or creates the customer by email.
- Validates stock availability per item.
- Computes row-level and order-level subtotals, tax and grand totals.
- Deducts stock atomically using pessimistic locking.
- Dispatches a `SendOrderConfirmationEmail` queued job.
- Returns `201` with the full order resource.

### GET `/api/customers/{email}/orders` — Order History

```http
GET /api/customers/thomas@example.com/orders
```

Returns paginated order history (15 per page). Returns `404` if email is unknown.

### GET `/api/products/low-stock` — Low Stock Alert

```http
GET /api/products/low-stock
```

Returns all products with `stock_quantity ≤ LOW_STOCK_THRESHOLD`, ordered by stock ascending.

---

## Running Tests

```bash
php artisan test tests/Feature/Api/ --no-coverage
```

Or the full suite:

```bash
php artisan test --no-coverage
```

**3 feature tests, 5 assertions** covering:

| Scenario | File |
|---|---|
| Low stock — filtered correctly | `LowStockTest` |
| Low stock — empty list | `LowStockTest` |
| Low stock — sort order | `LowStockTest` |

---

## Assumptions & Design Decisions

### Schema

**Price snapshot on `order_items`**: `unit_price` and `tax_percentage` are copied from the product at the moment of order creation. This means historical order totals remain accurate even if a product's price or tax rate changes later. This is standard practice for any billing system.

**`row_subtotal`, `row_tax`, `row_total`**: Each order item row stores its own computed totals so the order can be re-rendered without re-computing.

### Concurrency Safety

**Strategy: Pessimistic locking** via `Product::lockForUpdate()->findOrFail($id)` inside a `DB::transaction()`.

When two simultaneous requests target the same product:
1. Request A acquires a row-level exclusive lock on the product row.
2. Request B attempts the same lock and blocks (waits).
3. Request A validates stock → decrements → commits.
4. Request B resumes, reads the now-updated stock, fails validation → clean 422 response.

This guarantees no overselling under concurrent load. No optimistic locking / retry complexity needed for the scale of a retail counter.

### Queue Job

`SendOrderConfirmationEmail::dispatch($order)->afterCommit()`

The `->afterCommit()` modifier ensures the job is only pushed to the queue *after* the database transaction is successfully committed. Without this, a job could be dispatched for a transaction that later rolls back — the queue worker would then try to load an order that never existed.

### Tax Calculation

Used **bcmath** (`bcmul`, `bcadd`, `bcdiv`) instead of floating-point arithmetic to avoid precision issues with decimal money values (e.g., `0.1 + 0.2 !== 0.3` in IEEE 754).

### Customer on Order Creation

If an email is provided that already exists, the existing customer record is reused (`firstOrCreate`). If the email is new, a customer is created. The `name` field is required for new customers only (`required_without_all` in the FormRequest — though for simplicity the test always provides it).

### Low Stock Threshold

Configurable via `LOW_STOCK_THRESHOLD` in `.env`, read through `config/store.php`. Products with stock **at or below** the threshold are included (so a product at exactly the threshold value is flagged).

### Queue Driver

Used `database` driver (the `jobs` table was already present in the starter kit) to avoid requiring a Redis or other external dependency during evaluation.

---

## Project Structure

```
app/
├── Exceptions/InsufficientStockException.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── OrderController.php          ← POST /api/orders
│   │   ├── CustomerOrderController.php  ← GET  /api/customers/{email}/orders
│   │   └── ProductController.php       ← GET  /api/products/low-stock
│   ├── Requests/CreateOrderRequest.php
│   └── Resources/
│       ├── OrderResource.php
│       ├── OrderItemResource.php
│       └── ProductResource.php
├── Jobs/SendOrderConfirmationEmail.php
├── Livewire/NewOrder.php
├── Models/{Product,Customer,Order,OrderItem}.php
└── Services/OrderService.php           ← all business logic lives here

config/store.php                        ← LOW_STOCK_THRESHOLD

database/
├── migrations/                         ← products → customers → orders → order_items
├── factories/{Product,Customer}Factory.php
└── seeders/DatabaseSeeder.php

resources/
├── css/billing.css                     ← dark-mode premium UI styles
└── views/livewire/new-order.blade.php  ← Livewire billing screen

tests/Feature/Api/
└── LowStockTest.php
```

---

## Prompt Log

See the `/prompts` folder for screenshots of the AI-assisted prompts used during development.
