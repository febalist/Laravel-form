<?php

use Febalist\Laravel\Form\Form;

if (!function_exists('form')) {
    function form(): Form
    {
        return app(Form::class);
    }
}
