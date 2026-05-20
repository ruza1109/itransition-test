<?php

namespace App\Application\Products\Import;

class ImportTracker
{
    private int $processed = 0;
    private int $succeeded = 0;
    private array $skipped = [];
    private array $failed = [];

    public function incrementSucceeded(): void
    {
        $this->succeeded++;
    }

    public function recordSkipped(string $productCode, string $reason): void
    {
        $this->skipped[] = [
            'productCode' => $productCode,
            'reason' => $reason,
        ];
    }

    public function recordFailed(string $productCode, string $reason): void
    {
        $this->failed[] = [
            'productCode' => $productCode,
            'reason' => $reason
        ];
    }

    public function incrementProcessed(): void
    {
        $this->processed++;
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function getSucceeded(): int
    {
        return $this->succeeded;
    }

    public function getFailed(): array
    {
        return $this->failed;
    }

    public function getSkipped(): array
    {
        return $this->skipped;
    }
}
