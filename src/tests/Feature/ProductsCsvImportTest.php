<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductsCsvImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('tblProductData')) {
            Schema::create('tblProductData', function (Blueprint $table) {
                $table->increments('intProductDataId');
                $table->string('strProductName', 50);
                $table->string('strProductDesc', 255);
                $table->string('strProductCode', 10)->unique();
                $table->datetime('dtmAdded')->nullable();
                $table->datetime('dtmDiscontinued')->nullable();
                $table->timestamp('stmTimestamp')->useCurrent()->useCurrentOnUpdate();
            });
        }

        // Run migrations to apply your column alterations
        $this->artisan('migrate');
    }

    /**
     * Helper to create a temporary CSV file with the given rows.
     */
    private function createTempCsv(array $headers, array $rows): string
    {
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'stock_test_' . uniqid() . '.csv';
        $handle = fopen($tempFile, 'w');

        // Write headers
        fputcsv($handle, $headers);

        // Write rows
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
        return $tempFile;
    }

    /**
     * Test importing valid products successfully.
     */
    public function test_imports_valid_products_successfully(): void
    {
        $tempCsv = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0001', 'TV', '32” Tv', '10', '399.99', ''],
                ['P0008', 'CPU', 'Speedy', '12', '25.43', ''],
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0001',
            'strProductName' => 'TV',
            'strProductDesc' => '32” Tv',
            'intStock' => 10,
            'dcmCostInGbp' => 399.99,
            'dtmDiscontinued' => null,
        ]);

        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0008',
            'strProductName' => 'CPU',
            'strProductDesc' => 'Speedy',
            'intStock' => 12,
            'dcmCostInGbp' => 25.43,
            'dtmDiscontinued' => null,
        ]);

        unlink($tempCsv);
    }

    /**
     * Test discontinued items setting the discontinued date correctly on import.
     */
    public function test_imports_discontinued_product_with_current_date(): void
    {
        // Mock current time
        Carbon::setTestNow($now = Carbon::create(2026, 5, 19, 12, 0, 0));

        $tempCsv = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0002', 'Cd Player', 'Nice CD player', '11', '50.12', 'yes'],
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0002',
            'strProductName' => 'Cd Player',
            'strProductDesc' => 'Nice CD player',
            'intStock' => 11,
            'dcmCostInGbp' => 50.12,
            'dtmDiscontinued' => $now->toDateTimeString(),
        ]);

        unlink($tempCsv);
        Carbon::setTestNow(); // Reset time mock
    }

    /**
     * Test business rule: cost < $5 and stock < 10 will not be imported.
     */
    public function test_skips_product_if_cost_less_than_5_and_stock_less_than_10(): void
    {
        $tempCsv = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0001', 'Cheap low stock', 'Skip me', '9', '4.99', ''],    // Cost < 5 AND Stock < 10 -> SKIP
                ['P0002', 'Cheap high stock', 'Import me', '10', '4.99', ''], // Cost < 5 but Stock >= 10 -> IMPORT
                ['P0003', 'Expensive low stock', 'Import me too', '9', '5.00', ''], // Cost >= 5 and Stock < 10 -> IMPORT
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv])
            ->assertExitCode(0);

        // Product 1 should be skipped
        $this->assertDatabaseMissing('tblProductData', [
            'strProductCode' => 'P0001',
        ]);

        // Product 2 should be imported
        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0002',
        ]);

        // Product 3 should be imported
        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0003',
        ]);

        unlink($tempCsv);
    }

    /**
     * Test business rule: cost > $1000 will not be imported.
     */
    public function test_skips_product_if_cost_greater_than_1000(): void
    {
        $tempCsv = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0001', 'Too expensive', 'Skip me', '20', '1000.01', ''], // Cost > 1000 -> SKIP
                ['P0002', 'Just affordable', 'Import me', '20', '1000.00', ''], // Cost <= 1000 -> IMPORT
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv])
            ->assertExitCode(0);

        // Product 1 should be skipped
        $this->assertDatabaseMissing('tblProductData', [
            'strProductCode' => 'P0001',
        ]);

        // Product 2 should be imported
        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0002',
        ]);

        unlink($tempCsv);
    }

    /**
     * Test validation rules are enforced and invalid rows are skipped.
     */
    public function test_skips_product_if_validation_fails(): void
    {
        $tempCsv = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['', 'No Code', 'Description', '10', '10.00', ''], // Missing code (required)
                ['P12345678901', 'Long Code', 'Description', '10', '10.00', ''], // Code too long (> 10 chars)
                ['P0001', '', 'Description', '10', '10.00', ''], // Missing name (required)
                ['P0002', str_repeat('A', 51), 'Description', '10', '10.00', ''], // Name too long (> 50 chars)
                ['P0003', 'TV', str_repeat('B', 256), '10', '10.00', ''], // Description too long (> 255 chars)
                ['P0004', 'TV', 'Description', 'not-an-integer', '10.00', ''], // Stock is not an integer (integer)
                ['P0005', 'TV', 'Description', '10', '', ''], // Cost is missing
                ['P0006', 'TV', 'Description', '10', 'invalid-cost-string', ''], // Cost is invalid and sanitizes to null (required)
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv])
            ->assertExitCode(0);

        // Verify absolutely nothing got saved into the table since all rows were invalid
        $this->assertDatabaseCount('tblProductData', 0);

        unlink($tempCsv);
    }

    /**
     * Test that product codes are unique and duplicate imports are skipped.
     */
    public function test_skips_duplicate_product_codes(): void
    {
        // First import
        $tempCsv1 = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0001', 'TV', 'Original Tv', '10', '100.00', ''],
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv1])
            ->assertExitCode(0);

        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0001',
            'strProductName' => 'TV',
        ]);

        // Attempt second import with duplicate code P0001
        $tempCsv2 = $this->createTempCsv(
            ['Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued'],
            [
                ['P0001', 'TV New', 'Newer Tv', '20', '120.00', ''],
            ]
        );

        $this->artisan('app:products-csv-import', ['file' => $tempCsv2])
            ->assertExitCode(0);

        // Verify that the database contains the original product and was not overwritten or duplicated
        $this->assertDatabaseHas('tblProductData', [
            'strProductCode' => 'P0001',
            'strProductName' => 'TV',
            'strProductDesc' => 'Original Tv',
        ]);

        $this->assertDatabaseMissing('tblProductData', [
            'strProductName' => 'TV New',
        ]);

        unlink($tempCsv1);
        unlink($tempCsv2);
    }
}
