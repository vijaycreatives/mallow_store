<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'code' => $this->product->code,
            ],
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'tax_percentage' => $this->tax_percentage,
            'row_subtotal' => $this->row_subtotal,
            'row_tax' => $this->row_tax,
            'row_total' => $this->row_total,
        ];
    }
}
