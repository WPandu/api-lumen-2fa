<?php

namespace App\Providers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Imagick\Decoder;

class ImageServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->base64();
        $this->base64checkSize();
    }

    public function register()
    {
        $this->app->singleton('image', fn () => new ImageManager(['driver' => 'imagick']));

        $this->app->alias('image', 'Intervention\Image\ImageManager');
    }

    private function base64()
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Validator::extend('base64image', function ($attribute, $value) {
            $decoder = new Decoder($value);

            return $decoder->isDataUrl() || $decoder->isBase64();
        }, ':Attribute must be a base 64 image');
    }

    private function base64checkSize()
    {
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Validator::extend('base64imageSize', function ($attribute, $value, $parameters) {
            $size = strlen(base64_decode($value, true));
            $sizeKb = $size / 1024;

            return $sizeKb <= $parameters[0];
        });

        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Validator::replacer(
            'base64imageSize',
            fn ($message, $attribute, $rule, $parameters) => trans(
                'validation.max.file',
                ['attribute' => $attribute, 'max' => $parameters[0]]
            )
        );
    }
}
