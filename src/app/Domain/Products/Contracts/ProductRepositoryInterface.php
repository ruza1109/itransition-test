<?php

namespace App\Domain\Products\Contracts;

use App\Domain\Products\DTOs\ImportProductDto;

interface ProductRepositoryInterface
{
    public function store(ImportProductDto $productDto);
}
