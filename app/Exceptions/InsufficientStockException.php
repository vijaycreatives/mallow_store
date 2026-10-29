<?php

namespace App\Exceptions;

use App\Models\Product;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(public readonly Product $product, public readonly int $requested)
    {
        parent::__construct(
            "Insufficient stock for \"{$product->name}\" (code: {$product->code}). "
            ."Requested: {$requested}, Available: {$product->stock_quantity}."
        );
    }
}
