<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerOrderController extends Controller
{
    /**
     * GET /api/customers/{email}/orders
     *
     * Returns paginated order history for a customer identified by email.
     */
    public function index(string $email): AnonymousResourceCollection|JsonResponse
    {
        $customer = Customer::where('email', $email)->first();

        if (! $customer) {
            return response()->json([
                'message' => "No customer found with email: {$email}",
            ], 404);
        }

        $orders = $customer->orders()
            ->with('orderItems.product')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }
}
