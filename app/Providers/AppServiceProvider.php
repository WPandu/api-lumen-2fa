<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Sendinblue\Transport\SendinblueTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Validator;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //phpcs:ignore
        Validator::extend('base64', function ($attribute, $value, $parameters) {
            if (!$value) {
                return true;
            }

            return base64_encode(base64_decode($value, true)) === $value;
        });

        Validator::extend('recaptcha', 'App\\Validators\\ReCaptcha@validate');

        Mail::extend('sendinblue', fn () => (new SendinblueTransportFactory)->create(
            new Dsn(
                'sendinblue+api',
                'default',
                config('services.sendinblue.key')
            )
        ));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
