<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Register custom validation rules
        \Illuminate\Support\Facades\Validator::extend('email_rfc', function ($attribute, $value) {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }, 'The :attribute must be a valid RFC email address.');
    }
}
