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
            ->hasMigrations(
                '2013_12_03_161105_create_update_function',
                '2024_09_26_213249_create_thing_errors',
                '2024_09_26_213320_create_thing_hooks',
                '2024_09_26_220530_create_things',
                '2024_09_26_221314_create_thing_hook_clusters',
                '2024_10_05_020045_create_thing_results',
                '2024_10_05_020106_create_thing_result_callbacks',
                '2024_10_14_011529_create_thing_settings',
                '2024_10_14_021314_create_thing_setting_clusters',
            )

            ->runsMigrations()
        ;

    }


    /**
     * I encapsulate all the plugin logic in this class, which inherits from the plugin class
     *
     * @var PluginLogic
     */
    protected PluginLogic $plugin_logic;

    /**
     * This method overrides the base class empty method, that is called when the package is fully ready for use. Its called each time the laravel code runs
     *
     * Here we just create the new PluginLogic
     *
     * @return $this
     */
    public function packageBooted()
    {
        $this->plugin_logic = new PluginLogic();
        $this->plugin_logic->initialize();

        return $this;
    }

}
