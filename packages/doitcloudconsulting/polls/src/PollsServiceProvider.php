<?php

namespace doitcloudconsulting\polls;

use Illuminate\Support\ServiceProvider;

class PollsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->mergeConfigFrom(
            __DIR__ . '/config/SalesforceConfig.php', 'SalesforceConfig'
        );
        $this->app->make('doitcloudconsulting\polls\Controllers\Salesforce');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
