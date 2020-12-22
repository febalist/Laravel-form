<?php

if (!function_exists('form')) {
    /**
     * @return Febalist\LaravelForm\Form
     */
    function form()
    {
        return app(Febalist\Laravel\Form\Form::class);
    }
}
