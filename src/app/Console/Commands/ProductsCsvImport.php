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
        $this->info('Failed: '. count($tracker->getFailed()));
        $this->info('Skipped: '. count($tracker->getSkipped()));
        return;

        $rows = SimpleExcelReader::create($this->argument('file'))->getRows();

        $rows->each(function (array $row) {
            $sanitizedRow = $this->sanitizeRow($row);

            // Using max validation rule per database fields definition
            $validator = Validator::make($sanitizedRow, [
                'productCode' => 'required|string|max:10|unique:tblProductData,strProductCode',
                'productName' => 'required|string|max:50',
                'productDescription' => 'required|string|max:255',
                'stock' => 'required|integer',
                'cost' => 'required|string',
            ]);

            // Handling failed rows
            if ($validator->fails()) {
                Log::warning(sprintf("Product %s failed to import with errors: %s", $sanitizedRow['productCode'], implode(', ', $validator->errors()->all())));
                return;
            }

            // Business logic validation
            $productDto = new ImportProductDto(
                $sanitizedRow['productCode'],
                $sanitizedRow['productName'],
                $sanitizedRow['productDescription'],
                $sanitizedRow['stock'],
                $sanitizedRow['cost'],
                $sanitizedRow['discontinued'],
            );

            if (!$this->isValid($productDto)) {
                Log::warning(sprintf("Product %s failed for business rules.", $productDto->productCode));
                return;
            }

            // Saving product
            /** @var Product $product */
            $product = Product::make($productDto->toArray());
            $product->dtmDiscontinued = $productDto->isDiscontinued ? Carbon::now() : null;
            $product->save();
        });

    }
}
