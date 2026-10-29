<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /** Indian GST tax slabs commonly used in retail */
    private array $taxSlabs = [0, 5, 12, 18];

    /** Sample retail product names */
    private array $productNames = [
        'Colgate Toothpaste', 'Parle-G Biscuit', 'Amul Butter', 'Tata Salt',
        'Aashirvaad Atta', 'Maggi Noodles', 'Bru Coffee', 'Lifebuoy Soap',
        'Dettol Handwash', 'Sunsilk Shampoo', 'Dove Body Wash', 'Lux Soap',
        'Britannia Bread', 'Mother Dairy Milk', 'Tropicana Juice', 'Kurkure Chips',
        'Lay\'s Classic', 'Hide & Seek Biscuit', 'Good Day Biscuit', 'Bourbon Biscuit',
    ];

    public function definition(): array
    {
        static $nameIndex = 0;
        $name = $this->productNames[$nameIndex % count($this->productNames)];
        $nameIndex++;

        return [
            'name' => $name,
            'code' => strtoupper($this->faker->unique()->bothify('???###')),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'tax_percentage' => $this->faker->randomElement($this->taxSlabs),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }

    /** State: product with plenty of stock */
    public function inStock(int $qty = 50): static
    {
        return $this->state(['stock_quantity' => $qty]);
    }

    /** State: product out of stock */
    public function outOfStock(): static
    {
        return $this->state(['stock_quantity' => 0]);
    }
}
