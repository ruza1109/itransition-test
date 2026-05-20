<?php

namespace App\Application\Products\Import;

use App\Domain\Products\Contracts\ProductReaderInterface;
use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Domain\Products\DTOs\ImportProductDto;
use App\Domain\Products\Pipeline\Pipes\FilterCheapAndLowStock;
use App\Domain\Products\Pipeline\Pipes\FilterExpensiveItems;
use App\Domain\Products\Pipeline\Pipes\HandleDiscontinuedItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Pipeline;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

readonly class ImportProductsHandler
{
    public function __construct(
        private ProductReaderInterface $productReader,
        private ProductRepositoryInterface $productRepository,
    ) {}

    public function handle(string $path, bool $isTestMode): ImportTracker
    {
        $rows = $this->productReader->read($path);

        $tracker = new ImportTracker();

        $rows->each(function (array $row) use ($isTestMode, $tracker) {
            $tracker->incrementProcessed();

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
                $errors = $validator->errors()->all();

                Log::warning(
                    sprintf(
                        "Product %s failed to import with errors: %s",
                        $sanitizedRow['productCode'],
                        implode(', ', $errors))
                );

                $tracker->recordFailed(
                    $sanitizedRow['productCode'] ?? 'unknown product',
                    implode(', ', $errors)
                );

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

            $context = Pipeline::send(new ImportContext($productDto, $tracker))
                ->through([
                    FilterCheapAndLowStock::class,
                    FilterExpensiveItems::class,
                    HandleDiscontinuedItem::class,
                ])
                ->thenReturn();

            if (!$context->shouldImport()) {
                return;
            }

            if ($isTestMode) {
                $context->tracker->incrementSucceeded();
                return;
            }

            try {
                $this->productRepository->store($context->productDto);

                $context->tracker->incrementSucceeded();
            } catch (\Throwable $exception) {
                $context->tracker->recordFailed($context->productDto->productCode, $exception->getMessage());
            }
        });

        return $tracker;
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

    /**
     * Some basic cost sanitize
     */
    private function sanitizeCost(?string $cost): ?string
    {
        if (empty($cost)) {
            return null;
        }

        // Assuming commas act as thousands separators and removing them.
        // In a real-world scenario (if we need to cover these cases) I would use NumberFormatter based on the locale
        // to properly handle European formats where commas act as decimal separators.
        $cost = str_replace(',', '', $cost);

        $cleanCost = preg_replace('/[^\d.]/', '', $cost);

        if (!is_numeric($cleanCost)) {
            return null;
        }

        return $cleanCost;
    }
}
