<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    //    Illuminate\Translation\TranslationServiceProvider::class,
    TimeHunter\LaravelGoogleReCaptchaV3\Providers\GoogleReCaptchaV3ServiceProvider::class,
    Spatie\TranslationLoader\TranslationServiceProvider::class,

];
