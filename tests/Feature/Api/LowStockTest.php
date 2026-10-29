<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('returns only products below the low-stock threshold', function (): void {
    config(['store.low_stock_threshold' => 10]);

    Product::factory()->create(['stock_quantity' => 3]);   // below
    Product::factory()->create(['stock_quantity' => 10]);  // at threshold (included)
    Product::factory()->create(['stock_quantity' => 50]);  // above

    $response = getJson('/api/products/low-stock');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

it('returns an empty list when all products are well-stocked', function (): void {
    config(['store.low_stock_threshold' => 10]);

    Product::factory(3)->create(['stock_quantity' => 50]);

    getJson('/api/products/low-stock')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('returns products ordered by stock quantity ascending', function (): void {
    config(['store.low_stock_threshold' => 10]);

    Product::factory()->create(['stock_quantity' => 8]);
    Product::factory()->create(['stock_quantity' => 2]);
    Product::factory()->create(['stock_quantity' => 5]);

    $response = getJson('/api/products/low-stock');

    $stocks = collect($response->json('data'))->pluck('stock_quantity')->all();
    expect($stocks)->toBe([2, 5, 8]);
});
