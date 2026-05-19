<?php

namespace App\Console\Commands;

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
    public function handle(): void
    {
        $rows = SimpleExcelReader::create($this->argument('file'))->getRows();

        $this->withProgressBar($rows->count(), function () use ($rows) {
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
                $product->dtmDiscontinued = $productDto->discontinued ? Carbon::now() : null;
                $product->save();
            });

        });
    }

    private function sanitizeRow(array $row): array
    {
        // Using Str::squish for removing extra blank spaces
        // Added strip_tags also as an example (even do it's not needed for given .csv example)
        $productCode = isset($row['Product Code'])
            ? Str::squish(strip_tags($row['Product Code']))
            : null;
        $productName = isset($row['Product Name'])
            ? Str::squish(strip_tags($row['Product Name']))
            : null;
        $productDescription = isset($row['Product Description'])
            ? Str::squish(strip_tags($row['Product Description']))
            : null;
        $stock = isset($row['Stock']) && is_numeric($row['Stock'])
            ? (int)$row['Stock']
            : null;
        $cost = $this->sanitizeCost($row['Cost in GBP'] ?? null);
        // Assuming 'yes' is the only source of truth in terms of defining this field as discountable
        $discontinued = isset($row['Discontinued']) && strtolower(trim($row['Discontinued'])) === 'yes';

        return [
            'productCode' => $productCode,
            'productName' => $productName,
            'productDescription' => $productDescription,
            'stock' => $stock,
            'cost' => $cost,
            'discontinued' => $discontinued
        ];
    }

    private function sanitizeCost(?string $cost): ?string
    {
        if (empty($cost)) {
            return null;
        }

        $cleanCost = preg_replace('/[^\d.]/', '', $cost);

        if (!is_numeric($cleanCost)) {
            return null;
        }

        return $cleanCost;
    }

    /**
     * Checking for business rules
     */
    private function isValid(ImportProductDto $productDto): bool
    {
        if ($productDto->cost < 5 && $productDto->stock < 10) {
            return false;
        }

        if ($productDto->cost > 1000) {
            return false;
        }

        return true;
    }
}
