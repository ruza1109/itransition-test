<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ImportProductDto;
use App\Models\Product;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function store(ImportProductDto $productDto): Product
    {
        $data = $productDto->toArray();
        $data['dtmAdded'] = now();

        /** @var Product $product */
        $product = Product::create($data);

        return $product;
    }
}
