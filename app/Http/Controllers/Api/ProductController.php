<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * GET /api/products/low-stock
     *
     * Returns all products with stock at or below the configured threshold.
     * Threshold is set via LOW_STOCK_THRESHOLD in .env (default: 10).
     */
    public function lowStock(): AnonymousResourceCollection
    {
        $threshold = config('store.low_stock_threshold', 10);

        $products = Product::where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->get();

        return ProductResource::collection($products);
    }
}
