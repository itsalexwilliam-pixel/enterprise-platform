<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Built-in inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Custom artisan commands
Artisan::command('ev:status', function () {
    $this->info('Email Validator Pro - System Status');
    $this->line('Users:       ' . \App\Models\User::count());
    $this->line('Jobs Today:  ' . \App\Models\ValidationJob::whereDate('created_at', today())->count());
    $this->line('Validations: ' . \App\Models\ValidationResult::count());
    $this->line('Workers:     ' . \App\Models\Worker::where('status', 'running')->count() . ' running');
})->purpose('Show system status overview');

Artisan::command('ev:clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    $this->info('All caches cleared!');
})->purpose('Clear all application caches');
