<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tblProductData', function (Blueprint $table) {
            $table->decimal('dcmCostInGbp', 10);
            $table->integer('intStock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_product_data', function (Blueprint $table) {
            $table->dropColumn('dcmCostInGbp');
            $table->dropColumn('intStock');
        });
    }
};
