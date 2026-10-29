<div class="billing-page">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <header class="billing-header">
        <div class="header-inner">
            <div class="header-brand">
                <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                <div>
                    <h1 class="brand-title">Store Billing</h1>
                    <span class="brand-sub">New Order</span>
                </div>
            </div>
            <span class="wireframe-badge">MALLOW STORE</span>
        </div>
    </header>

    <main class="billing-main">

        {{-- ── Success Banner ────────────────────────────────────── --}}
        @if($createdOrder)
            <div class="alert alert-success" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div>
                    <strong>Order #{{ $createdOrder['id'] }} placed!</strong>
                    Grand total ₹{{ number_format($createdOrder['grand_total'], 2) }} for {{ $createdOrder['customer'] }}.
                    Confirmation email queued.
                </div>
                <button wire:click="$set('createdOrder', null)" class="alert-close">✕</button>
            </div>
        @endif

        {{-- ── Error Banner ────────────────────────────────────── --}}
        @if($errorMessage)
            <div class="alert alert-error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>{{ $errorMessage }}</div>
                <button wire:click="$set('errorMessage', null)" class="alert-close">✕</button>
            </div>
        @endif

        <div class="billing-grid">

            {{-- ── Left Column ──────────────────────────────────── --}}
            <div class="billing-left">

                {{-- Customer Card --}}
                <div class="card" id="customer-card">
                    <div class="card-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Customer
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email-input" class="form-label">Email</label>
                                <input
                                    id="email-input"
                                    type="email"
                                    wire:model.lazy="email"
                                    class="form-input"
                                    placeholder="e.g. thomas@example.com"
                                    autocomplete="email"
                                />
                                @error('email') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group">
                                <label for="name-input" class="form-label">Name</label>
                                <input
                                    id="name-input"
                                    type="text"
                                    wire:model="name"
                                    class="form-input"
                                    placeholder="auto-filled if email exists"
                                />
                                @error('name') <span class="field-error">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Products Card --}}
                <div class="card" id="products-card">
                    <div class="card-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        Products
                    </div>
                    <div class="card-body">

                        <div class="products-table-wrap">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Tax %</th>
                                        <th>Row Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                        @php
                                            $selectedProduct = $item['product_id']
                                                ? $this->products->firstWhere('id', (int) $item['product_id'])
                                                : null;
                                            $qty      = max(1, (int) ($item['quantity'] ?? 1));
                                            $rowSub   = $selectedProduct ? bcmul((string)$selectedProduct->price, (string)$qty, 2) : '0.00';
                                            $rowTax   = $selectedProduct ? bcmul($rowSub, bcdiv((string)$selectedProduct->tax_percentage, '100', 6), 2) : '0.00';
                                            $rowTotal = bcadd($rowSub, $rowTax, 2);
                                        @endphp
                                        <tr class="product-row" wire:key="item-{{ $index }}">
                                            <td>
                                                <select
                                                    id="product-select-{{ $index }}"
                                                    wire:model.live="items.{{ $index }}.product_id"
                                                    class="form-select"
                                                >
                                                    <option value="">— select product —</option>
                                                    @foreach($this->products as $product)
                                                        <option value="{{ $product->id }}">
                                                            {{ $product->name }} ({{ $product->code }}) · Stock: {{ $product->stock_quantity }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("items.{$index}.product_id") <span class="field-error">{{ $message }}</span> @enderror
                                            </td>
                                            <td>
                                                <input
                                                    id="qty-input-{{ $index }}"
                                                    type="number"
                                                    wire:model.live="items.{{ $index }}.quantity"
                                                    class="form-input qty-input"
                                                    min="1"
                                                    max="{{ $selectedProduct?->stock_quantity ?? 9999 }}"
                                                />
                                                @error("items.{$index}.quantity") <span class="field-error">{{ $message }}</span> @enderror
                                            </td>
                                            <td class="td-num">₹{{ $selectedProduct ? number_format($selectedProduct->price, 2) : '—' }}</td>
                                            <td class="td-num">{{ $selectedProduct ? $selectedProduct->tax_percentage : '—' }}%</td>
                                            <td class="td-num total-col">₹{{ number_format((float)$rowTotal, 2) }}</td>
                                            <td>
                                                @if(count($items) > 1)
                                                    <button
                                                        wire:click="removeItem({{ $index }})"
                                                        class="btn-remove"
                                                        title="Remove row"
                                                        id="remove-item-{{ $index }}"
                                                    >✕</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button wire:click="addItem" class="btn-add-product" id="add-product-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Product
                        </button>
                    </div>
                </div>

                {{-- Payment Card --}}
                <div class="card" id="payment-card">
                    <div class="card-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Payment
                    </div>
                    <div class="card-body">
                        <div class="totals-rows">
                            <div class="totals-row">
                                <span>Subtotal</span>
                                <span class="totals-value">₹{{ number_format((float)$this->subtotal, 2) }}</span>
                            </div>
                            <div class="totals-row">
                                <span>Tax</span>
                                <span class="totals-value">₹{{ number_format((float)$this->taxTotal, 2) }}</span>
                            </div>
                            <div class="totals-row grand">
                                <span>Grand Total</span>
                                <span class="totals-value grand-value">₹{{ number_format((float)$this->grandTotal, 2) }}</span>
                            </div>
                            <div class="totals-divider"></div>
                            <div class="totals-row amount-given-row">
                                <label for="amount-given" class="form-label">Amount Given by Customer</label>
                                <input
                                    id="amount-given"
                                    type="number"
                                    wire:model.live="amountGiven"
                                    class="form-input amount-input"
                                    placeholder="₹0.00"
                                    min="0"
                                    step="0.01"
                                />
                            </div>
                            @if($amountGiven !== '')
                                <div class="totals-row balance-row">
                                    <span>Balance to Return</span>
                                    <span class="totals-value {{ (float)$this->balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                                        ₹{{ number_format(abs((float)$this->balance), 2) }}
                                        {{ (float)$this->balance < 0 ? '(short)' : '' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <button
                            wire:click="generateBill"
                            wire:loading.attr="disabled"
                            class="btn-generate"
                            id="generate-bill-btn"
                        >
                            <span wire:loading.remove>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                Generate Bill
                            </span>
                            <span wire:loading>Processing…</span>
                        </button>
                        <p class="generate-hint">→ records order, deducts stock &amp; queues confirmation email</p>
                    </div>
                </div>

            </div>{{-- /billing-left --}}

            {{-- ── Right Column — Low Stock Alert ──────────────── --}}
            <aside class="billing-right" id="low-stock-panel">
                <div class="low-stock-card {{ count($this->lowStockProducts) === 0 ? 'low-stock-empty' : '' }}">
                    <div class="low-stock-header">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Low Stock Alert
                        <span class="low-stock-badge">{{ count($this->lowStockProducts) }}</span>
                    </div>

                    @if(count($this->lowStockProducts) === 0)
                        <p class="low-stock-all-good">All products are well stocked 🎉</p>
                    @else
                        <ul class="low-stock-list">
                            @foreach($this->lowStockProducts as $lsp)
                                <li class="low-stock-item {{ $lsp->stock_quantity === 0 ? 'out-of-stock' : '' }}">
                                    <span class="stock-dot {{ $lsp->stock_quantity === 0 ? 'dot-empty' : ($lsp->stock_quantity <= 3 ? 'dot-critical' : 'dot-low') }}"></span>
                                    <span class="lsp-name">{{ $lsp->name }}</span>
                                    <span class="lsp-qty">{{ $lsp->stock_quantity === 0 ? 'OUT' : $lsp->stock_quantity.' left' }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="low-stock-threshold">Threshold: ≤ {{ config('store.low_stock_threshold', 10) }} units</p>
                    @endif
                </div>
            </aside>

        </div>{{-- /billing-grid --}}
    </main>
</div>
