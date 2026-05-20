<?php

namespace App\Domain\Products\DTOs;

use Carbon\Carbon;

class ImportProductDto
{
    public function __construct(
        public string $productCode,
        public string $productName,
        public string $productDescription,
        public ?int $stock,
        public ?float $cost,
        public bool $isDiscontinued,
        public ?Carbon $discontinuedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'strProductCode' => $this->productCode,
            'strProductName' => $this->productName,
            'strProductDesc' => $this->productDescription,
            'intStock' => $this->stock,
            'dcmCostInGbp' => $this->cost,
            'dtmDiscontinued' => $this->discontinuedAt
        ];
    }
}
