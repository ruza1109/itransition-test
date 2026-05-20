<?php

namespace App\Console\Commands;

use App\Application\Products\Import\ImportProductsHandler;
use App\Application\Products\Import\ImportTracker;
use App\Domain\Products\DTOs\ImportProductDto;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

#[Signature('app:products-csv-import {file} {--test}')]
#[Description('Import products from CSV file')]
class ProductsCsvImport extends Command
{
    public function __construct(private readonly ImportProductsHandler $productsHandler)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tracker = $this->productsHandler->handle($this->argument('file'), $this->option('test'));
        $this->info('Processed: '. $tracker->getProcessed());
        $this->info('Succeeded: '. $tracker->getSucceeded());
        $this->error('Failed: '. count($tracker->getFailed()));
        foreach ($tracker->getFailed() as $error) {
            $this->error($error['productCode'] . ' - ' . $error['reason']);
        }

        $this->warn('Skipped: '. count($tracker->getSkipped()));

        foreach ($tracker->getSkipped() as $skip) {
            $this->alert($skip['productCode'] . ' - ' . $skip['reason']);
        }
    }
}
