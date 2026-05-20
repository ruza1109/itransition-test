<?php

namespace App\Application\Products\Import;

use App\Domain\Products\DTOs\ImportProductDto;

class ImportContext
{
    private bool $shouldImport = true;

    public function __construct(
        public ImportProductDto $productDto,
        public ImportTracker $tracker
    ) {}

    public function markAsSkipped(): void
    {
        $this->shouldImport = false;
    }

    public function shouldImport(): bool
    {
        return $this->shouldImport;
    }
}
