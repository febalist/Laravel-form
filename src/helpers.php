<?php

if (!function_exists('form')) {
    /**
     * @return Febalist\LaravelForm\Form
     */
    function form()
    {
        return app(Febalist\LaravelForm\Form::class);
    }
}
