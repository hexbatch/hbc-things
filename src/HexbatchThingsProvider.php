<?php
namespace Hexbatch\Things;

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

        return $this;
    }

}
