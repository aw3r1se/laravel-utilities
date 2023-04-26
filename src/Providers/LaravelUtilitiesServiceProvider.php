<?php

namespace Aw3r1se\LaravelUtilities\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelUtilitiesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations/2023_01_01_000001_create_images_table.php' =>
                    database_path('migrations/2023_01_01_000001_create_images_table.php'),
                __DIR__ . '/../../database/migrations/2023_01_01_000002_create_gallery_images_table.php' =>
                    database_path('migrations/2023_01_01_000002_create_gallery_images_table.php'),
                __DIR__ . '/../../database/migrations/2023_01_01_000003_create_documents_table.php' =>
                    database_path('migrations/2023_01_01_000003_create_images_table.php'),
            ], 'migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
