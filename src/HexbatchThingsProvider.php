<?php
namespace Hexbatch\Things;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
            //->hasRoute('thing_api') , note do require in the api section of the parent project for route param binding to work sometimes
            ->discoversMigrations()

            ->runsMigrations()

            ->hasInstallCommand(function(InstallCommand $command) {
                $command

                    ->copyAndRegisterServiceProviderInApp()
                    ;
            });
        ;

    }



    /**
     * called when the package is fully ready for use, each time the laravel code runs
     * @return $this
     */
    public function packageBooted()
    {

        Route::model('thing', Thing::class);
        Route::model('thing_hook', ThingHook::class);
        Route::model('thing_callback', ThingCallback::class);


        return $this;
    }

}
