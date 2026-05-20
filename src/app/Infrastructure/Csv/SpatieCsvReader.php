<?php

namespace App\Infrastructure\Csv;

use App\Domain\Products\Contracts\ProductReaderInterface;
use Illuminate\Support\LazyCollection;
use Spatie\SimpleExcel\SimpleExcelReader;

class SpatieCsvReader implements ProductReaderInterface
{
    public function read(string $path): LazyCollection
    {
        // In a real-world scenario, line endings (CRLF/LF) and UTF-8 encoding
        // should be standardized here before reading the file.
        return SimpleExcelReader::create($path)->getRows();
    }
}
