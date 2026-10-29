<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * POST /api/orders
     *
     * Creates an order: validates stock, computes totals (incl. tax),
     * deducts stock atomically, and dispatches a confirmation email job.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return (new OrderResource($order))
                ->response()
                ->setStatusCode(201);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'product' => [
                    'id' => $e->product->id,
                    'name' => $e->product->name,
                    'stock_available' => $e->product->stock_quantity,
                    'requested' => $e->requested,
                ],
            ], 422);
        }
    }
}
