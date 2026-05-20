<?php

namespace Tests\Unit;

use App\Application\Products\Import\ImportContext;
use App\Application\Products\Import\ImportTracker;
use App\Domain\Products\DTOs\ImportProductDto;
use App\Domain\Products\Pipeline\Pipes\FilterCheapAndLowStock;
use App\Domain\Products\Pipeline\Pipes\FilterExpensiveItems;
use App\Domain\Products\Pipeline\Pipes\HandleDiscontinuedItem;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PipesTest extends TestCase
{
    public function test_filter_cheap_and_low_stock_skips(): void
    {
        $dto = new ImportProductDto('P1', 'Name', 'Desc', 5, 4.00, false);
        $tracker = new ImportTracker();
        $context = new ImportContext($dto, $tracker);
        
        $pipe = new FilterCheapAndLowStock();
        $result = $pipe->handle($context, function ($passable) {
            return $passable;
        });

        $this->assertFalse($result->shouldImport());
        $this->assertCount(1, $tracker->getSkipped());
    }

    public function test_filter_cheap_and_low_stock_allows(): void
    {
        // Cost > 5, Stock < 10 -> allows
        $dto = new ImportProductDto('P1', 'Name', 'Desc', 5, 10.00, false);
        $tracker = new ImportTracker();
        $context = new ImportContext($dto, $tracker);
        
        $pipe = new FilterCheapAndLowStock();
        $result = $pipe->handle($context, function ($passable) {
            return $passable;
        });

        $this->assertTrue($result->shouldImport());
        $this->assertCount(0, $tracker->getSkipped());
    }

    public function test_filter_expensive_items_skips(): void
    {
        $dto = new ImportProductDto('P1', 'Name', 'Desc', 20, 1001.00, false);
        $tracker = new ImportTracker();
        $context = new ImportContext($dto, $tracker);
        
        $pipe = new FilterExpensiveItems();
        $result = $pipe->handle($context, function ($passable) {
            return $passable;
        });

        $this->assertFalse($result->shouldImport());
        $this->assertCount(1, $tracker->getSkipped());
    }

    public function test_handle_discontinued_item_sets_date(): void
    {
        $dto = new ImportProductDto('P1', 'Name', 'Desc', 20, 100.00, true);
        $tracker = new ImportTracker();
        $context = new ImportContext($dto, $tracker);
        
        $pipe = new HandleDiscontinuedItem();
        
        $result = $pipe->handle($context, function ($passable) {
            return $passable;
        });

        $this->assertNotNull($result->productDto->discontinuedAt);
        $this->assertInstanceOf(Carbon::class, $result->productDto->discontinuedAt);
    }
}
