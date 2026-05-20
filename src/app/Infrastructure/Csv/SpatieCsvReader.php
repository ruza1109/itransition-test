<?php

namespace App\Infrastructure\Csv;

use App\Domain\Products\Contracts\ProductReaderInterface;
use Illuminate\Support\LazyCollection;
use Spatie\SimpleExcel\SimpleExcelReader;

class SpatieCsvReader implements ProductReaderInterface
{
    public function read(string $path): LazyCollection
    {
        return SimpleExcelReader::create($path)->getRows();
    }
}
