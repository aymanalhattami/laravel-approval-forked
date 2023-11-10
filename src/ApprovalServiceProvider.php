<?php

namespace Approval;

use Illuminate\Support\ServiceProvider;

class ApprovalServiceProvider extends ServiceProvider
{
    /**
     * Boot up Approval.
     */
    public function boot(): void
    {
        $this->registerConfigurations();
        $this->registerMigrations();
    }

    /**
     * Register Approval configs.
     */
    private function registerConfigurations(): void
    {
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('approval.php'),
        ], 'config');
    }

    /**
     * Register Approval migrations.
     */
    private function registerMigrations(): void
    {
        $this->publishes([
            __DIR__.'/Migrations' => database_path('migrations'),
        ], 'migrations');
    }
}
