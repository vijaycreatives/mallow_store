<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    | Products with stock_quantity at or below this value will appear in the
    | low-stock alert endpoint. Override via LOW_STOCK_THRESHOLD in .env.
    */
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),
];
