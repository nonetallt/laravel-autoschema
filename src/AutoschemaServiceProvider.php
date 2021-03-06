<?php

namespace Nonetallt\LaravelAutoschema;

use Illuminate\Support\ServiceProvider;

class AutoschemaServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/autoschema.php' => config_path('autoschema.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/autoschema.php', 'autoschema');

        if($this->app->runningInConsole()) {
            $this->commands([
                CreateModelSchemasCommand::class
            ]);
        }
    }
}
