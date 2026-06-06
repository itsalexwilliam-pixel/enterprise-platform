<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Email Validation Services as singletons for performance
        $this->app->singleton(
            \App\Services\Validation\SyntaxValidator::class,
            fn () => new \App\Services\Validation\SyntaxValidator()
        );

        $this->app->singleton(
            \App\Services\Validation\DnsValidator::class,
            fn () => new \App\Services\Validation\DnsValidator()
        );

        $this->app->singleton(
            \App\Services\Validation\SmtpValidator::class,
            fn () => new \App\Services\Validation\SmtpValidator()
        );

        $this->app->singleton(
            \App\Services\Validation\DisposableDetector::class,
            fn () => new \App\Services\Validation\DisposableDetector()
        );

        $this->app->singleton(
            \App\Services\Validation\SpamTrapDetector::class,
            fn () => new \App\Services\Validation\SpamTrapDetector()
        );

        $this->app->singleton(
            \App\Services\Validation\ScoringEngine::class,
            fn () => new \App\Services\Validation\ScoringEngine()
        );

        $this->app->singleton(
            \App\Services\Validation\MailboxDetector::class,
            fn () => new \App\Services\Validation\MailboxDetector()
        );

        $this->app->singleton(\App\Services\Validation\EmailValidationService::class, function ($app) {
            return new \App\Services\Validation\EmailValidationService(
                $app->make(\App\Services\Validation\SyntaxValidator::class),
                $app->make(\App\Services\Validation\DnsValidator::class),
                $app->make(\App\Services\Validation\SmtpValidator::class),
                $app->make(\App\Services\Validation\DisposableDetector::class),
                $app->make(\App\Services\Validation\SpamTrapDetector::class),
                $app->make(\App\Services\Validation\ScoringEngine::class),
                $app->make(\App\Services\Validation\MailboxDetector::class),
            );
        });
    }

    public function boot(): void
    {
        // Use Bootstrap 5 pagination views (app uses Bootstrap, not Tailwind)
        Paginator::useBootstrapFive();

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Strict model behavior
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prevent lazy loading in local/testing
        if ($this->app->environment('local', 'testing')) {
            Model::preventLazyLoading();
        }
    }
}
