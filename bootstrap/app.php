<?php

use App\Http\Middleware\ApiLocaleKeyMiddleware;
use App\Http\Middleware\ApiSecretKeyMiddleware;
use App\Http\Middleware\EnsureCustomerNotBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api([
            ApiLocaleKeyMiddleware::class,

        ]);

        $middleware->alias([
            /**** OTHER MIDDLEWARE ALIASES ****/
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
            'appSecret' => ApiSecretKeyMiddleware::class,
            'customer.not_blocked' => EnsureCustomerNotBlocked::class,
            'ownerrez.webhook' => \App\Http\Middleware\OwnerRezWebhookAuth::class,
            'GoogleReCaptchaV3' => TimeHunter\LaravelGoogleReCaptchaV3\Facades\GoogleReCaptchaV3::class,

        ]);
        // reddirect if authenticated
        $middleware->redirectGuestsTo(fn () => route('home'));

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
