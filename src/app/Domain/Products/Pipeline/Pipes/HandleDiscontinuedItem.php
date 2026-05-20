<?php

namespace App\Domain\Products\Pipeline\Pipes;

use App\Application\Products\Import\ImportContext;
use Carbon\Carbon;
use Closure;

class HandleDiscontinuedItem
{
    public function handle(ImportContext $context, Closure $next): ImportContext
    {
        if ($context->productDto->isDiscontinued) {
            $context->productDto->discontinuedAt = Carbon::now();
        }

        return $next($context);
    }
}
