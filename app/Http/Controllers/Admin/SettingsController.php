<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name'           => config('app.name'),
            'app_url'            => config('app.url'),
            'smtp_timeout'       => config('validation.smtp.timeout'),
            'dns_timeout'        => config('validation.dns.timeout'),
            'bulk_chunk_size'    => config('validation.bulk.chunk_size'),
            'bulk_max_emails'    => config('validation.bulk.max_emails'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function clearCache()
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'All caches cleared successfully.');
    }

    public function optimise()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('success', 'Application optimised (caches rebuilt).');
    }
}
