<?php

namespace DoitCloudConsulting\Polls;

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
        $this->app->make('DoitCloudConsulting\Polls\Controllers\MainController');
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
