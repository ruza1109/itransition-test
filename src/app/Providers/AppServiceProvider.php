<?php

namespace App\Providers;

use App\Domain\Products\Contracts\ProductReaderInterface;
use App\Domain\Products\Contracts\ProductRepositoryInterface;
use App\Infrastructure\Csv\SpatieCsvReader;
use App\Infrastructure\Persistence\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductReaderInterface::class, SpatieCsvReader::class);
        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
