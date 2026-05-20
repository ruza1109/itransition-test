<?php

namespace App\Domain\Products\Contracts;

use Illuminate\Support\LazyCollection;

interface ProductReaderInterface
{
    public function read(string $path): LazyCollection;
}
