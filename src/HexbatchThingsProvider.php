<?php
namespace Hexbatch\Things;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;


class HexbatchThingsProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */


        $package
            ->name('hbc-things')
            ->hasConfigFile()
            ->hasRoute('api')
            ->discoversMigrations()

            ->runsMigrations()
        ;

    }



    /**
     * called when the package is fully ready for use, each time the laravel code runs
     * @return $this
     */
    public function packageBooted()
    {
        //todo add queue and batch events here

        Route::model('thing', Thing::class);
        Route::model('thing_hook', ThingHook::class);
        Route::model('thing_callback', ThingCallback::class);
        return $this;
    }

}
