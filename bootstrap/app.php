<?php

use App\Http\Middleware\ApiLocaleKeyMiddleware;
use App\Http\Middleware\ApiSecretKeyMiddleware;
use App\Http\Middleware\EnsureCustomerNotBlocked;
use App\Http\Middleware\EnsureStaffCan;
use App\Http\Middleware\OwnerRezWebhookAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use TimeHunter\LaravelGoogleReCaptchaV3\Facades\GoogleReCaptchaV3;

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
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
            'appSecret' => ApiSecretKeyMiddleware::class,
            'customer.not_blocked' => EnsureCustomerNotBlocked::class,
            'ownerrez.webhook' => OwnerRezWebhookAuth::class,
            'staff.can' => EnsureStaffCan::class,
            'GoogleReCaptchaV3' => GoogleReCaptchaV3::class,

        ]);
        // reddirect if authenticated
        $middleware->redirectGuestsTo(fn () => route('home'));

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
