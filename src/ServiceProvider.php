<?php

namespace Febalist\Laravel\Form;

use Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/../views' => resource_path('views/vendor/form')]);
        $this->loadViewsFrom(__DIR__.'/../views', 'form');

        Blade::aliasComponent('form::components.form_group', 'form_group');
        Blade::aliasComponent('form::components.form_group_help', 'form_group_help');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/form.php', 'form');
        $this->app->singleton(Form::class, function ($app) {
            return new Form(bs());
        });
    }
}
