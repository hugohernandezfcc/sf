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
        $this->app->make('doitcloudconsulting\polls\Controllers\MainController');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}