<?php

namespace App\Domain\Products\Pipeline\Pipes;

use App\Application\Products\Import\ImportContext;
use Closure;

class FilterExpensiveItems
{
    public function handle(ImportContext $context, Closure $next): ImportContext
    {
        if ($context->productDto->cost > 1000) {
            $context->tracker->recordSkipped($context->productDto->productCode, 'Product cost is higher than 1000');
            $context->markAsSkipped();

            return $context;
        }

        return $next($context);
    }
}
