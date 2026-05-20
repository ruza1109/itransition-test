<?php

namespace Tests\Unit;

use App\Domain\Products\DTOs\ImportProductDto;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ImportProductDtoTest extends TestCase
{
    public function test_to_array_maps_correctly(): void
    {
        $now = Carbon::now();

        $dto = new ImportProductDto(
            'P0001',
            'Test Product',
            'Test Description',
            10,
            15.50,
            true,
            $now
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'strProductCode' => 'P0001',
            'strProductName' => 'Test Product',
            'strProductDesc' => 'Test Description',
            'intStock' => 10,
            'dcmCostInGbp' => 15.50,
            'dtmDiscontinued' => $now
        ], $array);
    }
}
