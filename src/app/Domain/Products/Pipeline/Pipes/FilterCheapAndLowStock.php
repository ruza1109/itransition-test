<?php

namespace App\Domain\Products\Pipeline\Pipes;

use App\Application\Products\Import\ImportContext;
use Closure;

class FilterCheapAndLowStock
{
    public function handle(ImportContext $context, Closure $next): ImportContext
    {
        if ($context->productDto->cost < 5 && $context->productDto->stock < 10) {
            $context->tracker->recordSkipped($context->productDto->productCode, 'Product cost is lower than 5 and stock is lower than 10');
            $context->markAsSkipped();

            return $context;
        }

        return $next($context);
    }
}
